<?php

namespace Modules\SimpleAudit\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class TenantConnectionManager
{
    protected $centralConnection;

    public function centralConnectionName()
    {
        if ($this->centralConnection) {
            return $this->centralConnection;
        }

        /*
         * Use the same CENTRAL connection priority as the host ERP.
         *
         * The application defines `system` as the canonical central database
         * connection and tenancy never repoints it. Older Simple Audit builds
         * checked `mysql` first. On installations where `mysql` is currently
         * pointed at a tenant/copied database, that can expose only one local
         * tenants row (for example tenant 100) instead of the complete central
         * tenant registry.
         *
         * Priority:
         *   1. host `system` connection;
         *   2. tenancy.database.central_connection;
         *   3. Simple Audit override;
         *   4. current/default compatibility connections.
         */
        $candidates = $this->centralConnectionCandidates();

        foreach ($candidates as $name) {
            if (!$this->connectionConfigured($name)) {
                continue;
            }

            try {
                if (!Schema::connection($name)->hasTable('tenants')) {
                    continue;
                }

                // A populated tenant registry is authoritative. In particular,
                // never replace a valid `system` registry with the active tenant
                // connection merely because that tenant also contains this table.
                if ((int) DB::connection($name)->table('tenants')->count() > 0) {
                    return $this->centralConnection = $name;
                }
            } catch (Throwable $e) {
                // Continue to the next configured compatibility connection.
            }
        }

        // Keep the host's central connection preference even on a freshly
        // installed estate whose tenants table is currently empty.
        foreach ($candidates as $name) {
            if ($this->connectionConfigured($name)) {
                return $this->centralConnection = $name;
            }
        }

        return $this->centralConnection = (string) config('database.default');
    }

    /**
     * Central DB candidates in the host application's authoritative order.
     */
    protected function centralConnectionCandidates()
    {
        $system = !empty(config('database.connections.system.database')) ? 'system' : null;
        $tenancyCentral = config('tenancy.database.central_connection');
        $configured = config('simpleaudit.central_connection');

        return array_values(array_unique(array_filter(array_merge(
            [$system, $tenancyCentral, $configured, config('database.default'), 'central', 'mysql'],
            array_keys((array) config('database.connections', []))
        ))));
    }

    public function listTenants()
    {
        $connection = $this->centralConnectionName();
        try {
            if (!Schema::connection($connection)->hasTable('tenants')) {
                return [];
            }

            $rows = DB::connection($connection)->table('tenants')->orderBy('id')->get();
            $domains = [];
            if (Schema::connection($connection)->hasTable('domains')) {
                DB::connection($connection)->table('domains')->orderBy('domain')->get()->each(function ($row) use (&$domains) {
                    $domains[$row->tenant_id][] = $row->domain;
                });
            }

            return $rows->map(function ($row) use ($domains) {
                $tenantId = (string) $row->id;
                $domain = isset($domains[$row->id][0]) ? $domains[$row->id][0] : null;
                $data = $this->decodeTenantData(isset($row->data) ? $row->data : null);
                $name = $this->firstTenantValue($data, ['name', 'tenant_name', 'business_name', 'company_name']);

                $parts = [$tenantId];
                if ($name !== null && trim((string) $name) !== '' && trim((string) $name) !== $tenantId) {
                    $parts[] = trim((string) $name);
                }
                if ($domain) {
                    $parts[] = (string) $domain;
                }

                return [
                    'id' => $tenantId,
                    'label' => implode(' — ', $parts),
                    'domain' => $domain,
                ];
            })->values()->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function currentTenantIdFromHost()
    {
        $host = request() ? request()->getHost() : null;
        if (!$host) {
            return null;
        }

        $connection = $this->centralConnectionName();
        try {
            if (!Schema::connection($connection)->hasTable('domains')) {
                return null;
            }
            $tenantId = DB::connection($connection)->table('domains')
                ->where('domain', $host)
                ->value('tenant_id');
            return $tenantId ? (string) $tenantId : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function connectionForTenant($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->currentTenantIdFromHost();

        // If the active/default connection already looks like a tenant DB, prefer
        // it when no tenant was explicitly selected. This keeps tenant-host usage
        // fast and avoids an unnecessary second connection.
        if (!$tenantId && $this->looksLikeTenantDatabase(config('database.default'))) {
            return config('database.default');
        }

        if ($tenantId && $this->defaultConnectionMatchesTenant($tenantId)) {
            return config('database.default');
        }

        if (!$tenantId) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.tenant_required'));
        }

        $central = $this->centralConnectionName();
        if (!Schema::connection($central)->hasTable('tenants')) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.central_tenants_unavailable'));
        }

        $tenant = DB::connection($central)->table('tenants')->where('id', $tenantId)->first();
        if (!$tenant) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.tenant_not_found'));
        }

        $data = $this->decodeTenantData(isset($tenant->data) ? $tenant->data : null);
        $database = $this->resolveTenantDatabaseName($tenantId, $data, $central);

        if (!$database) {
            throw new RuntimeException(__('simpleaudit::simpleaudit.tenant_database_name_missing'));
        }

        /*
         * IMPORTANT - Central Super Admin -> tenant database access
         * ---------------------------------------------------------
         * The central database login is not necessarily permitted to open every
         * tenant database. The host application already carries a dedicated
         * mysql_tenant connection for this purpose, so Simple Audit must prefer
         * that connection template instead of cloning the central connection.
         *
         * We also support Stancl's configured template connection and common
         * legacy names. The central connection remains a final compatibility
         * fallback for installations where one MySQL account owns all schemas.
         */
        $runtimeName = config('simpleaudit.runtime_tenant_connection', 'sau_runtime_tenant');
        $attempted = [];
        $lastError = null;

        foreach ($this->tenantConnectionTemplates($central) as $templateName => $base) {
            $attempted[] = $templateName;
            $runtime = $this->buildRuntimeTenantConfig($base, $data, $database);

            Config::set('database.connections.' . $runtimeName, $runtime);
            DB::purge($runtimeName);

            try {
                DB::connection($runtimeName)->getPdo();

                if (!Schema::connection($runtimeName)->hasTable('business')) {
                    throw new RuntimeException(__('simpleaudit::simpleaudit.tenant_business_table_missing'));
                }

                return $runtimeName;
            } catch (Throwable $e) {
                $lastError = $e;
                DB::purge($runtimeName);
            }
        }

        $message = $lastError ? $lastError->getMessage() : 'No usable tenant database connection template is configured.';
        if ($attempted) {
            $message .= ' [connection templates tried: ' . implode(', ', $attempted) . ']';
        }

        throw new RuntimeException(
            __('simpleaudit::simpleaudit.tenant_connection_failed', ['message' => $message]),
            0,
            $lastError
        );
    }

    /**
     * Resolve the physical tenant database name used by this ERP.
     *
     * IMPORTANT: the central tenants.id is a tenant KEY, not necessarily a
     * database name. For example tenant id "100" maps to "nivasa_100" on this
     * installation. Older v1.0.7 incorrectly fell back to database "100" when
     * tenants.data did not contain tenancy_db_name, which produced MySQL 1044.
     *
     * Resolution order deliberately mirrors the host application's tenancy
     * bootstrap while remaining module-owned and read-only:
     *   1. explicit database name stored in tenants.data;
     *   2. configured TENANT_DATABASE_PREFIX / SAU_TENANT_DATABASE_PREFIX;
     *   3. Stancl tenancy database prefix when configured;
     *   4. prefix derived from the central database name (e.g. nivasa_base -> nivasa_);
     *   5. a conservative nivasa_ compatibility fallback.
     */
    public function resolveTenantDatabaseName($tenantId, array $data = null, $central = null)
    {
        $tenantId = trim((string) $tenantId);
        if ($tenantId === '' || !preg_match('/^[A-Za-z0-9_\-]+$/', $tenantId)) {
            return null;
        }

        if ($data === null) {
            $central = $central ?: $this->centralConnectionName();
            try {
                if (!Schema::connection($central)->hasTable('tenants')) {
                    return null;
                }
                $row = DB::connection($central)->table('tenants')->where('id', $tenantId)->first();
                if (!$row) {
                    return null;
                }
                $data = $this->decodeTenantData(isset($row->data) ? $row->data : null);
            } catch (Throwable $e) {
                return null;
            }
        }

        $explicit = $this->firstTenantValue($data, config('simpleaudit.tenant_data_keys.database', []));
        if ($explicit !== null && $explicit !== '') {
            return (string) $explicit;
        }

        $prefixes = [];
        foreach ([
            config('simpleaudit.tenant_database_prefix'),
            config('tenancy.database.prefix'),
        ] as $prefix) {
            if (is_string($prefix) && $prefix !== '') {
                $prefixes[] = $prefix;
            }
        }

        $central = $central ?: $this->centralConnectionName();
        $derived = $this->deriveTenantPrefixFromCentralDatabase($central);
        if ($derived !== null && $derived !== '') {
            $prefixes[] = $derived;
        }

        // The application's own TenancyServiceProvider uses nivasa_ as its
        // default TENANT_DATABASE_PREFIX. Keep this only as the final fallback.
        $prefixes[] = 'nivasa_';

        foreach (array_values(array_unique($prefixes)) as $prefix) {
            // If the tenant key is already a physical database name, do not
            // duplicate the prefix (e.g. nivasa_nivasa_100).
            if (strpos($tenantId, $prefix) === 0) {
                return $tenantId;
            }
            return $prefix . $tenantId;
        }

        return null;
    }

    protected function deriveTenantPrefixFromCentralDatabase($central)
    {
        if (!$central || !$this->connectionConfigured($central)) {
            return null;
        }

        try {
            $name = (string) DB::connection($central)->getDatabaseName();
        } catch (Throwable $e) {
            return null;
        }

        if ($name === '') {
            return null;
        }

        // Standard estates use names such as nivasa_base / syzeasy_base.
        if (preg_match('/^(.*?_)(?:base|central|master)$/i', $name, $m)) {
            return $m[1];
        }

        // Compatibility for names without the underscore separator.
        if (preg_match('/^(.*?)(?:base|central|master)$/i', $name, $m) && $m[1] !== '') {
            return rtrim($m[1], '_') . '_';
        }

        return null;
    }

    /**
     * Return usable connection templates in safest order.
     *
     * The key is only a diagnostic label. The value is a Laravel DB connection
     * config array which is cloned into the module-owned runtime connection.
     */
    protected function tenantConnectionTemplates($central)
    {
        $names = [];

        $configured = config('simpleaudit.tenant_connection');
        if ($configured) {
            $names[] = $configured;
        }

        // This application's proven cross-database tenant connection.
        $names[] = 'mysql_tenant';

        // Stancl tenancy installations can name a template connection here.
        $stanclTemplate = config('tenancy.database.template_tenant_connection');
        if ($stanclTemplate) {
            $names[] = $stanclTemplate;
        }

        // Common compatibility names used by older deployments.
        $names[] = 'tenant';
        $names[] = 'tenant_mysql';

        // Include any explicitly configured connection whose name identifies it
        // as tenant-facing. This keeps the module portable without hard-coding
        // credentials or reading another module's files.
        foreach (array_keys((array) config('database.connections', [])) as $name) {
            if (stripos((string) $name, 'tenant') !== false) {
                $names[] = $name;
            }
        }

        // Last fallbacks for installations where the same MySQL user can access
        // both central and tenant schemas.
        $names[] = $central;
        $names[] = config('database.default');
        $names[] = 'mysql';

        $templates = [];
        foreach (array_values(array_unique(array_filter($names))) as $name) {
            $base = (array) config('database.connections.' . $name, []);
            if (!$base) {
                continue;
            }
            if (($base['driver'] ?? 'mysql') !== 'mysql') {
                continue;
            }
            $templates[$name] = $base;
        }

        return $templates;
    }

    /**
     * Build a module-owned runtime connection without changing the application's
     * central/default or mysql_tenant connection objects.
     */
    protected function buildRuntimeTenantConfig(array $base, array $data, $database)
    {
        $runtime = $base;
        $runtime['driver'] = 'mysql';
        $runtime['database'] = $database;

        $host = $this->firstTenantValue($data, config('simpleaudit.tenant_data_keys.host', []));
        $port = $this->firstTenantValue($data, config('simpleaudit.tenant_data_keys.port', []));
        $username = $this->firstTenantValue($data, config('simpleaudit.tenant_data_keys.username', []));
        $password = $this->firstTenantValue($data, config('simpleaudit.tenant_data_keys.password', []));

        if ($host !== null && $host !== '') {
            $runtime['host'] = $host;
        }
        if ($port !== null && $port !== '') {
            $runtime['port'] = $port;
        }
        if ($username !== null && $username !== '') {
            $runtime['username'] = $username;
        }
        if ($password !== null && $password !== '') {
            $runtime['password'] = $password;
        }

        // A URL may contain a different database/user and override the individual
        // fields in Laravel's connector. Remove it for this runtime connection so
        // the selected tenant database and credentials above are authoritative.
        if (array_key_exists('url', $runtime)) {
            $runtime['url'] = null;
        }

        return $runtime;
    }

    public function tenantIdForConnection($connection)
    {
        try {
            if (!Schema::connection($connection)->hasTable('business')) {
                return null;
            }
            $value = DB::connection($connection)->table('business')
                ->whereNotNull('tenant_id')
                ->where('tenant_id', '<>', '')
                ->value('tenant_id');
            return $value ? (string) $value : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function defaultConnectionMatchesTenant($tenantId)
    {
        $default = config('database.default');
        if (!$this->looksLikeTenantDatabase($default)) {
            return false;
        }

        try {
            if (!Schema::connection($default)->hasColumn('business', 'tenant_id')) {
                return false;
            }
            return DB::connection($default)->table('business')
                ->where('tenant_id', $tenantId)
                ->exists();
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function looksLikeTenantDatabase($connection)
    {
        if (!$connection || !$this->connectionConfigured($connection)) {
            return false;
        }
        try {
            return Schema::connection($connection)->hasTable('business')
                && Schema::connection($connection)->hasTable('transactions')
                && Schema::connection($connection)->hasTable('purchase_lines');
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function decodeTenantData($raw)
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_object($raw)) {
            return (array) $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function firstTenantValue(array $data, array $keys)
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    protected function connectionConfigured($name)
    {
        return $name && config('database.connections.' . $name) !== null;
    }
}
