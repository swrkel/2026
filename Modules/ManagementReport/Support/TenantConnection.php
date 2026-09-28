<?php

namespace Modules\ManagementReport\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Resolve the database used by Management Report without assuming that every
 * business lives in a separate Stancl tenant database.
 *
 * Supported deployment modes:
 *  1. Dynamic tenant domain -> Stancl tenant DB on the normal mysql connection.
 *  2. Legacy copied tenant -> mysql already points directly at its tenant DB.
 *  3. Central/shared business -> the authenticated business intentionally lives
 *     in the application's current mysql database together with other businesses,
 *     and every report query remains scoped by business_id.
 *
 * A shared/central business is detected in two safe ways:
 *  - the current host is explicitly listed in tenancy.central_domains; or
 *  - tenancy has not been initialized yet, authentication already succeeded,
 *    and the authenticated business row physically exists in the current mysql
 *    database.  The second rule is required by older central installations that
 *    host normal businesses in the master database but were never added to
 *    tenancy.central_domains.
 */
class TenantConnection
{
    private static bool $activated = false;

    public static function activate(): string
    {
        static::assertInitialized();

        if (static::$activated) {
            return 'mysql';
        }

        $database = static::expectedDatabaseName();
        if ($database === '') {
            throw new RuntimeException('Management Report could not determine the active business database name.');
        }

        $configured = (string) config('database.connections.mysql.database');

        // Purge, rather than only disconnect, so Laravel cannot reuse a
        // Connection object that was originally created for another database.
        if (strcasecmp($configured, $database) !== 0) {
            config(['database.connections.mysql.database' => $database]);
        }

        DB::purge('mysql');
        DB::setDefaultConnection('mysql');

        try {
            $row = DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name');
            $actual = (string) ($row->database_name ?? '');
        } catch (\Throwable $e) {
            throw new RuntimeException(
                sprintf('Management Report could not connect to business database "%s".', $database),
                0,
                $e
            );
        }

        if ($actual === '' || strcasecmp($actual, $database) !== 0) {
            throw new RuntimeException(sprintf(
                'Management Report blocked a cross-database query. Expected database "%s", but MySQL selected "%s".',
                $database,
                $actual !== '' ? $actual : '[none]'
            ));
        }

        static::$activated = true;

        return 'mysql';
    }

    public static function name(): string
    {
        return static::activate();
    }

    public static function db(): ConnectionInterface
    {
        return DB::connection(static::activate());
    }

    public static function schema(): Builder
    {
        return Schema::connection(static::activate());
    }

    public static function databaseName(): string
    {
        static::activate();

        return (string) DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name')->database_name;
    }

    /**
     * Resolve the database expected for the current request.
     */
    public static function expectedDatabaseName(): string
    {
        // A shared/central database can legitimately host one or more businesses
        // directly in the current mysql database. Do not force such a business
        // into a synthetic/legacy tenant DB merely because a stale tenant record
        // with a matching business_id also exists.
        if (static::isCentralBusinessDatabase()) {
            return (string) config('database.connections.mysql.database');
        }

        if (static::isStandaloneTenantDatabase()) {
            return (string) config('database.connections.mysql.database');
        }

        static::assertInitialized();
        $tenantId = (string) tenant('id');
        if ($tenantId === '') {
            return '';
        }

        return (string) config('tenancy.database.prefix', 'nivasa_')
            . $tenantId
            . (string) config('tenancy.database.suffix', '');
    }

    public static function assertInitialized(): void
    {
        if (static::isCentralBusinessDatabase() || static::isStandaloneTenantDatabase()) {
            return;
        }

        if (!function_exists('tenancy')) {
            throw new RuntimeException('Management Report requires a valid tenant or central business database context.');
        }

        try {
            if (!tenancy()->initialized || !tenancy()->tenant) {
                throw new RuntimeException(
                    'Management Report business database context is not initialized.'
                );
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Management Report could not verify the business database context.', 0, $e);
        }
    }

    /**
     * Shared/central installations may keep several businesses in the same
     * database. In that mode the current mysql database is the correct
     * operational database and business_id provides data isolation.
     *
     * Do not rely only on tenancy.central_domains here. Some older central
     * deployments use an ordinary deployment hostname (for example a numbered
     * system host) while their businesses still live in the current master DB.
     * Because this middleware runs after auth, a matching authenticated business
     * row in the current DB is strong evidence that the request must stay there.
     */
    public static function isCentralBusinessDatabase(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return false;
            }
        } catch (\Throwable $exception) {
            // Continue with host/database verification.
        }

        $configured = (string) config('database.connections.mysql.database');
        if ($configured === '') {
            return false;
        }

        try {
            $row = DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name');
            $actual = (string) ($row->database_name ?? '');
        } catch (\Throwable $exception) {
            return false;
        }

        if ($actual === '' || strcasecmp($actual, $configured) !== 0) {
            return false;
        }

        $host = static::normalizeHost((string) request()->getHost());
        if ($host !== '' && static::isConfiguredCentralHost($host)) {
            return true;
        }

        return static::authenticatedBusinessExistsOnCurrentDatabase();
    }

    /**
     * Verify that the business from the already-authenticated web request is
     * actually stored in the currently selected mysql database.
     *
     * This deliberately uses the raw mysql connection instead of this class's
     * activate()/schema() helpers to avoid recursion while database context is
     * still being resolved.
     */
    private static function authenticatedBusinessExistsOnCurrentDatabase(): bool
    {
        $businessId = static::authenticatedBusinessId();
        if ($businessId < 1) {
            return false;
        }

        try {
            $connection = DB::connection('mysql');
            $schema = $connection->getSchemaBuilder();

            foreach (['business', 'businesses'] as $table) {
                if (!$schema->hasTable($table)) {
                    continue;
                }

                if ($connection->table($table)->where('id', $businessId)->exists()) {
                    return true;
                }
            }
        } catch (\Throwable $exception) {
            return false;
        }

        return false;
    }

    private static function authenticatedBusinessId(): int
    {
        try {
            $request = request();
            $user = $request->user();

            return (int) (
                ($user->business_id ?? null)
                ?: $request->session()->get('user.business_id')
                ?: $request->session()->get('business.id')
            );
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    /**
     * Legacy copied deployments already point mysql at one tenant database and
     * intentionally have no central tenants/domains rows. Accept that mode
     * only when both the configured and live database names match each other
     * and the database name is tied to the current host's first label.
     */
    public static function isStandaloneTenantDatabase(): bool
    {
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                return false;
            }
        } catch (\Throwable $exception) {
            // Continue with standalone verification.
        }

        $configured = (string) config('database.connections.mysql.database');
        if ($configured === '') {
            return false;
        }

        $prefix = (string) config('tenancy.database.prefix', '');
        $suffix = (string) config('tenancy.database.suffix', '');
        if (app()->runningInConsole()) {
            $hostMatches = $prefix !== ''
                && str_starts_with(strtolower($configured), strtolower($prefix))
                && ($suffix === '' || str_ends_with(strtolower($configured), strtolower($suffix)));
        } else {
            $hostKey = strtolower((string) strtok((string) request()->getHost(), '.'));
            if ($hostKey === '') {
                return false;
            }

            $expected = $prefix . $hostKey . $suffix;
            $hostMatches = strcasecmp($configured, $expected) === 0
                || str_ends_with(strtolower($configured), '_' . $hostKey . strtolower($suffix));
        }

        if (!$hostMatches) {
            return false;
        }

        try {
            $row = DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name');
            $actual = (string) ($row->database_name ?? '');
        } catch (\Throwable $exception) {
            return false;
        }

        return $actual !== '' && strcasecmp($actual, $configured) === 0;
    }

    /**
     * Check the host against Stancl's configured central domain list. The
     * normalizer also tolerates JSON/comma-delimited legacy config values and
     * www/non-www variants.
     */
    private static function isConfiguredCentralHost(string $host): bool
    {
        $configured = config('tenancy.central_domains', []);

        if (is_string($configured)) {
            $decoded = json_decode($configured, true);
            if (is_array($decoded)) {
                $configured = $decoded;
            } else {
                $configured = preg_split('/\s*,\s*/', $configured, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            }
        }

        foreach ((array) $configured as $candidate) {
            $candidate = static::normalizeHost((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            if ($candidate === $host) {
                return true;
            }

            if (preg_replace('/^www\./', '', $candidate) === preg_replace('/^www\./', '', $host)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeHost(string $host): string
    {
        $host = trim(strtolower($host));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $parsed = parse_url($host, PHP_URL_HOST);
            $host = is_string($parsed) ? strtolower($parsed) : $host;
        }

        return preg_replace('/:\d+$/', '', trim($host, " \t\n\r\0\x0B/")) ?: '';
    }

    public static function reset(): void
    {
        static::$activated = false;
    }
}
