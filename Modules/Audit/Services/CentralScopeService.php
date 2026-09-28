<?php

namespace Modules\Audit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CentralScopeService
{
    const CENTRAL_KEY = 'central';

    protected $tenants;
    protected $centralDefaultConnection;
    protected $centralDefaultDatabase;
    protected $centralMysqlDatabase;

    public function __construct(TenantContextManager $tenants)
    {
        $this->tenants = $tenants;

        // Capture the central connection state once, before any tenant is
        // initialized. Some installations switch the existing `mysql`
        // connection itself during tenancy initialization. If initialization
        // fails (for example an inaccessible tenant database), simply calling
        // tenancy()->end() is not enough to put Laravel's connection back on
        // the central database.
        $this->centralDefaultConnection = (string) config('database.default', 'mysql');
        $this->centralDefaultDatabase = config('database.connections.' . $this->centralDefaultConnection . '.database');
        $this->centralMysqlDatabase = config('database.connections.mysql.database');
    }

    public function sources(): array
    {
        // Central source discovery must always begin from the central DB.
        // This also protects repeated AJAX/filter calls after a previous
        // tenant source failed during initialization.
        $this->tenants->end();
        $this->restoreCentralConnection();

        $sources = [];
        $centralDb = $this->centralDatabaseName();
        $sources[] = [
            'key' => self::CENTRAL_KEY,
            'type' => 'central',
            'tenant_id' => null,
            'label' => 'Central Database' . ($centralDb ? ' (' . $centralDb . ')' : ''),
            'domain' => null,
            'database' => $centralDb,
        ];

        $domainsByTenant = $this->domainsByTenant();
        foreach ($this->tenants->allTenants() as $tenant) {
            try {
                $id = method_exists($tenant, 'getTenantKey')
                    ? (string) $tenant->getTenantKey()
                    : (string) $tenant->id;
                if ($id === '') {
                    continue;
                }
                $domain = $domainsByTenant[$id] ?? null;
                $sources[] = [
                    'key' => $this->tenantKey($id),
                    'type' => 'tenant',
                    'tenant_id' => $id,
                    'label' => $domain ? ($id . ' — ' . $domain) : $id,
                    'domain' => $domain,
                    'database' => null,
                ];
            } catch (\Throwable $e) {
            }
        }

        usort($sources, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'central' ? -1 : 1;
            }
            return strnatcasecmp((string) $a['label'], (string) $b['label']);
        });

        return $sources;
    }

    public function sourceMap(): array
    {
        $out = [];
        foreach ($this->sources() as $source) {
            $out[$source['key']] = $source;
        }
        return $out;
    }

    public function normalizeSourceKeys($keys): array
    {
        $requested = array_values(array_filter(array_map('strval', (array) $keys), function ($v) {
            return trim($v) !== '';
        }));
        $map = $this->sourceMap();
        if (!$requested) {
            return array_keys($map);
        }

        $out = [];
        foreach ($requested as $key) {
            if (isset($map[$key])) {
                $out[] = $key;
            }
        }
        return array_values(array_unique($out));
    }

    public function selectedSources($keys): array
    {
        $map = $this->sourceMap();
        $out = [];
        foreach ($this->normalizeSourceKeys($keys) as $key) {
            if (isset($map[$key])) {
                $out[] = $map[$key];
            }
        }
        return $out;
    }

    public function businessOptions($sourceKeys): array
    {
        $rows = [];
        foreach ($this->selectedSources($sourceKeys) as $source) {
            try {
                $this->withSource($source, function () use (&$rows, $source) {
                    if (!Schema::hasTable('business')) {
                        return;
                    }
                    $q = DB::table('business')->select('id', 'name')->orderBy('name');
                    foreach ($q->get() as $business) {
                        $rows[] = [
                            'key' => $this->encodeBusinessKey($source['key'], $business->id),
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'business_id' => (string) $business->id,
                            'name' => (string) ($business->name ?: ('Business #' . $business->id)),
                            'label' => (string) ($business->name ?: ('Business #' . $business->id)) . ' — ' . $source['label'],
                        ];
                    }
                });
            } catch (\Throwable $e) {
            }
        }
        usort($rows, function ($a, $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });
        return $rows;
    }

    public function locationOptions($sourceKeys, $businessKeys = []): array
    {
        $selectedBusiness = $this->groupBusinessKeys($businessKeys);
        $businessFilterActive = count((array) $businessKeys) > 0;
        $rows = [];
        foreach ($this->selectedSources($sourceKeys) as $source) {
            // If one or more businesses were explicitly selected, locations must
            // come only from sources/businesses represented in that selection.
            if ($businessFilterActive && empty($selectedBusiness[$source['key']])) {
                continue;
            }
            try {
                $this->withSource($source, function () use (&$rows, $source, $selectedBusiness, $businessFilterActive) {
                    $table = $this->locationTable();
                    if (!$table) {
                        return;
                    }
                    $q = DB::table($table)->select('id', 'name');
                    $hasBusiness = Schema::hasColumn($table, 'business_id');
                    if ($hasBusiness) {
                        $q->addSelect('business_id');
                        if ($businessFilterActive) {
                            $q->whereIn('business_id', $selectedBusiness[$source['key']] ?? ['__none__']);
                        }
                    }
                    $businessNames = $this->businessNameMap();
                    foreach ($q->orderBy('name')->get() as $location) {
                        $businessId = $hasBusiness && isset($location->business_id) ? (string) $location->business_id : null;
                        $businessName = $businessId !== null ? ($businessNames[$businessId] ?? ('Business #' . $businessId)) : 'Business not linked';
                        $rows[] = [
                            'key' => $this->encodeLocationKey($source['key'], $businessId, $location->id),
                            'source_key' => $source['key'],
                            'source_label' => $source['label'],
                            'business_id' => $businessId,
                            'business_name' => $businessName,
                            'location_id' => (string) $location->id,
                            'name' => (string) ($location->name ?: ('Location #' . $location->id)),
                            'label' => (string) ($location->name ?: ('Location #' . $location->id)) . ' — ' . $businessName . ' — ' . $source['label'],
                        ];
                    }
                });
            } catch (\Throwable $e) {
            }
        }
        usort($rows, function ($a, $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });
        return $rows;
    }

    public function groupBusinessKeys($businessKeys): array
    {
        $out = [];
        foreach ((array) $businessKeys as $key) {
            $decoded = $this->decodeCompositeKey($key);
            if (!$decoded || ($decoded['kind'] ?? null) !== 'business') {
                continue;
            }
            $source = (string) ($decoded['source'] ?? '');
            $id = (string) ($decoded['business_id'] ?? '');
            if ($source !== '' && $id !== '') {
                $out[$source][] = $id;
            }
        }
        foreach ($out as $source => $ids) {
            $out[$source] = array_values(array_unique($ids));
        }
        return $out;
    }

    public function groupLocationKeys($locationKeys): array
    {
        $out = [];
        foreach ((array) $locationKeys as $key) {
            $decoded = $this->decodeCompositeKey($key);
            if (!$decoded || ($decoded['kind'] ?? null) !== 'location') {
                continue;
            }
            $source = (string) ($decoded['source'] ?? '');
            $locationId = (string) ($decoded['location_id'] ?? '');
            if ($source === '' || $locationId === '') {
                continue;
            }
            $out[$source][] = [
                'business_id' => isset($decoded['business_id']) && $decoded['business_id'] !== '' ? (string) $decoded['business_id'] : null,
                'location_id' => $locationId,
            ];
        }
        return $out;
    }

    public function encodeBusinessKey(string $sourceKey, $businessId): string
    {
        return $this->encodeCompositeKey([
            'kind' => 'business',
            'source' => $sourceKey,
            'business_id' => (string) $businessId,
        ]);
    }

    public function encodeLocationKey(string $sourceKey, $businessId, $locationId): string
    {
        return $this->encodeCompositeKey([
            'kind' => 'location',
            'source' => $sourceKey,
            'business_id' => $businessId === null ? '' : (string) $businessId,
            'location_id' => (string) $locationId,
        ]);
    }

    public function decodeCompositeKey($value): ?array
    {
        try {
            $value = (string) $value;
            if ($value === '') {
                return null;
            }
            $padding = strlen($value) % 4;
            if ($padding) {
                $value .= str_repeat('=', 4 - $padding);
            }
            $json = base64_decode(strtr($value, '-_', '+/'), true);
            if ($json === false) {
                return null;
            }
            $data = json_decode($json, true);
            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function withSource(array $source, callable $callback)
    {
        // Always restore the central DB in an OUTER finally block. This is
        // intentionally wider than tenancy initialization so even a failed
        // initialize() cannot leak a tenant database into the rest of the
        // central request (layout/auth/sidebar queries included).
        try {
            $this->tenants->end();
            $this->restoreCentralConnection();

            if (($source['type'] ?? null) === 'central') {
                $source['database'] = $this->centralDatabaseName();
                return $callback($source);
            }

            $tenantId = (string) ($source['tenant_id'] ?? '');
            $tenant = $this->tenants->resolve($tenantId);
            if (!$tenant) {
                throw new \RuntimeException('Tenant ' . $tenantId . ' could not be resolved.');
            }

            // initialize() itself may throw after changing Laravel's database
            // configuration, therefore it must be inside this try/finally.
            $this->tenants->initialize($tenant);
            $source['database'] = $this->currentDatabaseName();

            return $callback($source);
        } finally {
            $this->tenants->end();
            $this->restoreCentralConnection();
        }
    }

    /**
     * Put Laravel back on the central connection after visiting a tenant.
     *
     * The ERP has installations where Stancl tenancy mutates the `mysql`
     * connection directly. Purging after restoring config is important: an
     * already-open PDO can otherwise remain connected to the tenant database.
     */
    public function restoreCentralConnection(): void
    {
        try {
            if ($this->centralDefaultConnection !== '') {
                config(['database.default' => $this->centralDefaultConnection]);
            }

            $connections = [];
            if ($this->centralDefaultConnection !== '' && $this->centralDefaultDatabase !== null) {
                config([
                    'database.connections.' . $this->centralDefaultConnection . '.database'
                        => $this->centralDefaultDatabase,
                ]);
                $connections[] = $this->centralDefaultConnection;
            }

            if ($this->centralMysqlDatabase !== null) {
                config(['database.connections.mysql.database' => $this->centralMysqlDatabase]);
                $connections[] = 'mysql';
            }

            foreach (array_values(array_unique(array_filter($connections))) as $connection) {
                try {
                    DB::purge($connection);
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
            // Restoration is best-effort here; callers still receive the
            // original tenant/source exception and can mark that source as
            // failed safely.
        }
    }

    public function currentDatabaseName(): ?string
    {
        try {
            return (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function centralDatabaseName(): ?string
    {
        // Use the request-start snapshot, never the possibly-mutated live
        // tenancy config. This keeps labels and reconnection logic stable even
        // after an inaccessible tenant attempted to initialize.
        if ($this->centralMysqlDatabase !== null && $this->centralMysqlDatabase !== '') {
            return (string) $this->centralMysqlDatabase;
        }
        if ($this->centralDefaultDatabase !== null && $this->centralDefaultDatabase !== '') {
            return (string) $this->centralDefaultDatabase;
        }

        try {
            return (string) DB::connection('mysql')->getDatabaseName();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function businessNameMap(): array
    {
        try {
            if (!Schema::hasTable('business')) {
                return [];
            }
            return DB::table('business')->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                return [(string) $id => (string) $name];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function locationNameMap(): array
    {
        try {
            $table = $this->locationTable();
            if (!$table) {
                return [];
            }
            return DB::table($table)->pluck('name', 'id')->mapWithKeys(function ($name, $id) {
                return [(string) $id => (string) $name];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function locationBusinessMap(): array
    {
        try {
            $table = $this->locationTable();
            if (!$table || !Schema::hasColumn($table, 'business_id')) {
                return [];
            }
            return DB::table($table)->pluck('business_id', 'id')->mapWithKeys(function ($businessId, $id) {
                return [(string) $id => $businessId === null ? null : (string) $businessId];
            })->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function locationTable(): ?string
    {
        try {
            if (Schema::hasTable('business_locations')) {
                return 'business_locations';
            }
            if (Schema::hasTable('locations')) {
                return 'locations';
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    public function hasAuditTables(): bool
    {
        try {
            return Schema::hasTable('audit_runs') && Schema::hasTable('audit_findings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function sourceKeyForTenant($tenantId): string
    {
        return $this->tenantKey((string) $tenantId);
    }

    protected function tenantKey(string $id): string
    {
        return 'tenant:' . $id;
    }

    protected function encodeCompositeKey(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }

    protected function domainsByTenant(): array
    {
        $out = [];
        $domainModel = config('tenancy.domain_model', 'Stancl\\Tenancy\\Database\\Models\\Domain');
        if (!class_exists($domainModel)) {
            return $out;
        }
        try {
            foreach ($domainModel::query()->orderBy('domain')->get() as $domain) {
                $tenantId = isset($domain->tenant_id) ? (string) $domain->tenant_id : '';
                $name = isset($domain->domain) ? trim((string) $domain->domain) : '';
                if ($tenantId === '' || $name === '' || substr($name, -1) === '.') {
                    continue;
                }
                if (!isset($out[$tenantId])) {
                    $out[$tenantId] = $name;
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }
}
