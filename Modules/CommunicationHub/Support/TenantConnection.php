<?php

namespace Modules\CommunicationHub\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Communication Hub tenant connection resolver.
 *
 * Operational Communication Hub data belongs to the tenant database, not the
 * central database. This resolver uses the application's mysql_tenant
 * connection when it is available, and falls back to the current default
 * connection only when a tenant connection is not configured.
 */
class TenantConnection
{
    public static function name(): ?string
    {
        try {
            if (function_exists('tenant_db')) {
                tenant_db();
            }
        } catch (\Throwable $e) {
            // Continue to fallback below.
        }

        try {
            $tenantDb = config('database.connections.mysql_tenant.database');
            if (!empty($tenantDb)) {
                DB::connection('mysql_tenant')->getPdo();
                return 'mysql_tenant';
            }
        } catch (\Throwable $e) {
            // Fall back to the current default connection.
        }

        return null;
    }

    public static function db()
    {
        $connection = static::name();
        return $connection ? DB::connection($connection) : DB::connection();
    }

    public static function schema()
    {
        $connection = static::name();
        return $connection ? Schema::connection($connection) : Schema::connection(DB::getDefaultConnection());
    }

    public static function hasTable(string $table): bool
    {
        try {
            return static::schema()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function hasColumn(string $table, string $column): bool
    {
        try {
            return static::hasTable($table) && static::schema()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
