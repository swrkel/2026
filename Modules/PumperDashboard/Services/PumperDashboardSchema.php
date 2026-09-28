<?php

namespace Modules\PumperDashboard\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Request-local schema capability cache for tenant databases.
 *
 * Schema metadata checks query information_schema. Keeping the result in this
 * process for the current request avoids repeating that cost while preserving
 * the active tenant connection/database boundary.
 */
class PumperDashboardSchema
{
    /** @var array<string, bool> */
    private static array $tableCache = [];

    /** @var array<string, bool> */
    private static array $columnCache = [];

    private static function prefix(): string
    {
        $connection = DB::connection();

        return $connection->getName() . ':' . (string) $connection->getDatabaseName();
    }

    public static function hasTable(string $table): bool
    {
        $key = self::prefix() . ':table:' . $table;

        return self::$tableCache[$key] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = self::prefix() . ':column:' . $table . ':' . $column;

        return self::$columnCache[$key] ??= (
            self::hasTable($table) && Schema::hasColumn($table, $column)
        );
    }
}
