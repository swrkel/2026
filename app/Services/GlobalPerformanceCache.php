<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GlobalPerformanceCache
{
    public static function scopeKey(string $name, array $parts = []): string
    {
        $connection = DB::getDefaultConnection();
        $database = (string) (config("database.connections.$connection.database") ?: 'unknown');
        $host = strtolower((string) request()->getHost());
        $suffix = implode(':', array_map(static fn ($value) => is_scalar($value) ? (string) $value : md5(serialize($value)), $parts));

        return 'erp_perf:' . sha1($host . '|' . $connection . '|' . $database) . ':' . $name . ($suffix !== '' ? ':' . $suffix : '');
    }

    public static function remember(string $name, array $parts, int $seconds, callable $callback)
    {
        return Cache::remember(self::scopeKey($name, $parts), now()->addSeconds($seconds), $callback);
    }

    public static function forget(string $name, array $parts = []): void
    {
        Cache::forget(self::scopeKey($name, $parts));
    }
}
