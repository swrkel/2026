<?php

namespace Modules\Customers\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Request-local, tenant/database-aware schema capability cache.
 *
 * Laravel's Schema::hasTable()/hasColumn() calls query database metadata.
 * Customers pages call these checks repeatedly, so cache them for the current
 * PHP request while keeping separate entries for each tenant connection/database.
 */
final class SchemaCache
{
    /** @var array<string, bool> */
    private static $tables = [];

    /** @var array<string, bool> */
    private static $columns = [];

    /** @var array<string, array<int, string>> */
    private static $listings = [];

    /** @var array<string, bool> */
    private static $persistentKeys = [];

    private const PERSISTENT_CACHE_MINUTES = 30;

    public static function hasTable(string $table): bool
    {
        $key = self::connectionKey() . '|table|' . $table;

        if (!array_key_exists($key, self::$tables)) {
            $cacheKey = self::persistentKey('table', $table);
            self::$persistentKeys[$cacheKey] = true;

            try {
                self::$tables[$key] = (bool) Cache::remember(
                    $cacheKey,
                    now()->addMinutes(self::PERSISTENT_CACHE_MINUTES),
                    static fn () => Schema::hasTable($table)
                );
            } catch (\Throwable $e) {
                self::$tables[$key] = Schema::hasTable($table);
            }
        }

        return self::$tables[$key];
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = self::connectionKey() . '|column|' . $table . '|' . $column;

        if (!array_key_exists($key, self::$columns)) {
            // One column-list lookup per table replaces dozens of individual
            // INFORMATION_SCHEMA calls in the Customer Register/Total Due AJAX.
            self::$columns[$key] = in_array($column, self::columns($table), true);
        }

        return self::$columns[$key];
    }

    /**
     * @return array<int, string>
     */
    public static function columns(string $table): array
    {
        $key = self::connectionKey() . '|listing|' . $table;

        if (!array_key_exists($key, self::$listings)) {
            if (!self::hasTable($table)) {
                self::$listings[$key] = [];
                return self::$listings[$key];
            }

            $cacheKey = self::persistentKey('columns', $table);
            self::$persistentKeys[$cacheKey] = true;

            try {
                self::$listings[$key] = (array) Cache::remember(
                    $cacheKey,
                    now()->addMinutes(self::PERSISTENT_CACHE_MINUTES),
                    static fn () => Schema::getColumnListing($table)
                );
            } catch (\Throwable $e) {
                self::$listings[$key] = Schema::getColumnListing($table);
            }
        }

        return self::$listings[$key];
    }

    public static function clear(): void
    {
        foreach (array_keys(self::$persistentKeys) as $cacheKey) {
            try {
                Cache::forget($cacheKey);
            } catch (\Throwable $e) {
                // Schema availability must not depend on the cache backend.
            }
        }

        self::$tables = [];
        self::$columns = [];
        self::$listings = [];
        self::$persistentKeys = [];
    }

    private static function persistentKey(string $kind, string $table): string
    {
        return 'customers:schema:' . sha1(self::connectionKey() . '|' . $kind . '|' . $table);
    }

    private static function connectionKey(): string
    {
        try {
            $connection = DB::connection();

            return (string) $connection->getName() . '|' . (string) $connection->getDatabaseName();
        } catch (\Throwable $e) {
            return 'default';
        }
    }
}
