<?php

namespace Modules\DailyCollectionSW\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaCapabilities
{
    private static array $tables = [];
    private static array $columns = [];

    public static function hasTable(string $table): bool
    {
        $key = self::connectionKey() . ':table:' . $table;
        return self::$tables[$key] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = self::connectionKey() . ':column:' . $table . ':' . $column;
        return self::$columns[$key] ??= Schema::hasColumn($table, $column);
    }

    private static function connectionKey(): string
    {
        $connection = DB::connection();
        return $connection->getName() . ':' . ($connection->getDatabaseName() ?: 'unknown');
    }
}
