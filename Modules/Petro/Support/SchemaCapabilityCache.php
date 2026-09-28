<?php

namespace Modules\Petro\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaCapabilityCache
{
    /** @var array<string, bool> */
    private static array $cache = [];

    public static function hasTable(string $table, ?string $connection = null): bool
    {
        $key = self::key($connection, 'table', $table);

        if (! array_key_exists($key, self::$cache)) {
            $builder = Schema::connection($connection ?: DB::getDefaultConnection());
            self::$cache[$key] = $builder->hasTable($table);
        }

        return self::$cache[$key];
    }

    public static function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        $key = self::key($connection, 'column', $table . '.' . $column);

        if (! array_key_exists($key, self::$cache)) {
            $builder = Schema::connection($connection ?: DB::getDefaultConnection());
            self::$cache[$key] = $builder->hasColumn($table, $column);
        }

        return self::$cache[$key];
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    private static function key(?string $connection, string $type, string $name): string
    {
        $connectionName = $connection ?: DB::getDefaultConnection();
        $databaseName = (string) DB::connection($connectionName)->getDatabaseName();

        return $connectionName . '|' . $databaseName . '|' . $type . '|' . $name;
    }
}
