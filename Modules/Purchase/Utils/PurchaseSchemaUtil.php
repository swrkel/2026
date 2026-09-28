<?php

namespace Modules\Purchase\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseSchemaUtil
{
    /** @var array<string, bool> */
    protected static array $tables = [];

    /** @var array<string, array<int, string>> */
    protected static array $columns = [];

    public function tableExists(string $table): bool
    {
        $key = $this->cacheKey($table);

        if (! array_key_exists($key, static::$tables)) {
            static::$tables[$key] = Schema::hasTable($table);
        }

        return static::$tables[$key];
    }

    /** @return array<int, string> */
    public function columns(string $table): array
    {
        $key = $this->cacheKey($table);

        if (! $this->tableExists($table)) {
            return [];
        }

        if (! array_key_exists($key, static::$columns)) {
            static::$columns[$key] = Schema::getColumnListing($table);
        }

        return static::$columns[$key];
    }

    public function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    /**
     * Keep only columns that exist in the current tenant schema. The cache key
     * includes both connection and database names so different tenant schemas
     * cannot share stale column metadata in long-running PHP processes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function filter(string $table, array $data): array
    {
        $columns = $this->columns($table);

        if ($columns === []) {
            return [];
        }

        return array_intersect_key($data, array_flip($columns));
    }

    protected function cacheKey(string $table): string
    {
        try {
            $connection = DB::connection();
            $connectionName = (string) $connection->getName();
            $databaseName = (string) $connection->getDatabaseName();
        } catch (\Throwable) {
            $connectionName = 'default';
            $databaseName = 'unknown';
        }

        return $connectionName . '|' . $databaseName . '|' . $table;
    }
}
