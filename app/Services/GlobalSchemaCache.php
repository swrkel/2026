<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GlobalSchemaCache
{
    private static array $requestCache = [];

    public static function hasTable(string $table, ?string $connection = null): bool
    {
        $connection = $connection ?: DB::getDefaultConnection();
        $key = self::key($connection, 'table', $table);

        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        try {
            $value = self::remember($key, function () use ($connection, $table) {
                return Schema::connection($connection)->hasTable($table);
            });
        } catch (\Throwable $e) {
            $value = false;
        }

        return self::$requestCache[$key] = (bool) $value;
    }

    public static function hasColumn(string $table, string $column, ?string $connection = null): bool
    {
        $connection = $connection ?: DB::getDefaultConnection();
        $key = self::key($connection, 'column', $table . '.' . $column);

        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        try {
            $value = self::remember($key, function () use ($connection, $table, $column) {
                return Schema::connection($connection)->hasColumn($table, $column);
            });
        } catch (\Throwable $e) {
            $value = false;
        }

        return self::$requestCache[$key] = (bool) $value;
    }

    public static function columns(string $table, ?string $connection = null): array
    {
        $connection = $connection ?: DB::getDefaultConnection();
        $key = self::key($connection, 'columns', $table);

        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        try {
            $value = self::remember($key, function () use ($connection, $table) {
                return Schema::connection($connection)->getColumnListing($table);
            });
        } catch (\Throwable $e) {
            $value = [];
        }

        return self::$requestCache[$key] = (array) $value;
    }

    public static function forget(?string $connection = null): void
    {
        self::$requestCache = [];
        // Persistent schema keys expire automatically. The version bump allows
        // immediate invalidation after migrations without cache-store scans.
        Cache::forever(self::versionKey($connection), time());
    }

    private static function remember(string $key, callable $resolver)
    {
        if (! config('global_performance.enabled', true)) {
            return $resolver();
        }

        return Cache::remember($key, config('global_performance.schema_ttl', 600), $resolver);
    }

    private static function key(string $connection, string $type, string $name): string
    {
        $database = (string) config("database.connections.{$connection}.database", 'unknown');
        $host = (string) config("database.connections.{$connection}.host", 'localhost');
        $version = Cache::get(self::versionKey($connection), 1);

        return 'gpo:schema:' . sha1($host . '|' . $connection . '|' . $database) . ':' . $version . ':' . $type . ':' . sha1($name);
    }

    private static function versionKey(?string $connection): string
    {
        return 'gpo:schema-version:' . ($connection ?: DB::getDefaultConnection());
    }
}
