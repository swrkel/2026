<?php

namespace Modules\PetroDirect\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaCapabilityCache
{
    /** @var array<string, bool> */
    private static array $cache = [];

    /** @var array<string, array<int, string>> */
    private static array $columnListings = [];

    /** @var array<string, string|null> */
    private static array $columnTypes = [];

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
            if (! self::hasTable($table, $connection)) {
                self::$cache[$key] = false;
            } else {
                $builder = Schema::connection($connection ?: DB::getDefaultConnection());
                self::$cache[$key] = $builder->hasColumn($table, $column);
            }
        }

        return self::$cache[$key];
    }

    /**
     * Return the real columns available in the active tenant database.
     *
     * PetroDirect is deployed to databases that were upgraded at different
     * times.  Payment writes must therefore be based on the current tenant's
     * table capabilities instead of assuming every optional linkage column is
     * already present.
     *
     * @return array<int, string>
     */
    public static function columns(string $table, ?string $connection = null): array
    {
        $key = self::key($connection, 'columns', $table);

        if (! array_key_exists($key, self::$columnListings)) {
            if (! self::hasTable($table, $connection)) {
                self::$columnListings[$key] = [];
            } else {
                try {
                    $builder = Schema::connection($connection ?: DB::getDefaultConnection());
                    self::$columnListings[$key] = array_values($builder->getColumnListing($table));
                } catch (\Throwable $exception) {
                    self::$columnListings[$key] = [];
                }
            }
        }

        return self::$columnListings[$key];
    }

    /**
     * Keep only keys which are real columns in the active tenant table.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function filterPayload(string $table, array $payload, ?string $connection = null): array
    {
        $columns = self::columns($table, $connection);
        if (empty($columns)) {
            return [];
        }

        return array_intersect_key($payload, array_flip($columns));
    }

    public static function columnType(string $table, string $column, ?string $connection = null): ?string
    {
        $key = self::key($connection, 'column_type', $table . '.' . $column);

        if (! array_key_exists($key, self::$columnTypes)) {
            if (! self::hasColumn($table, $column, $connection)) {
                self::$columnTypes[$key] = null;
            } else {
                try {
                    $builder = Schema::connection($connection ?: DB::getDefaultConnection());
                    self::$columnTypes[$key] = strtolower((string) $builder->getColumnType($table, $column));
                } catch (\Throwable $exception) {
                    self::$columnTypes[$key] = null;
                }
            }
        }

        return self::$columnTypes[$key];
    }

    public static function flush(): void
    {
        self::$cache = [];
        self::$columnListings = [];
        self::$columnTypes = [];
    }

    private static function key(?string $connection, string $type, string $name): string
    {
        $connectionName = $connection ?: DB::getDefaultConnection();
        $databaseName = (string) DB::connection($connectionName)->getDatabaseName();

        return $connectionName . '|' . $databaseName . '|' . $type . '|' . $name;
    }
}
