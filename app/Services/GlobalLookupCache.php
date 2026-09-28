<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GlobalLookupCache
{
    private static array $requestMemo = [];

    public static function remember(string $group, array $dimensions, Closure $resolver, ?int $seconds = null)
    {
        $key = self::key($group, $dimensions);
        if (array_key_exists($key, self::$requestMemo)) {
            return self::$requestMemo[$key];
        }

        $ttl = $seconds ?? (int) config('global_performance.lookup_ttl', 120);
        $value = $ttl > 0 ? Cache::remember($key, $ttl, $resolver) : $resolver();

        return self::$requestMemo[$key] = $value;
    }

    public static function key(string $group, array $dimensions = []): string
    {
        $connection = DB::getDefaultConnection();
        $database = (string) config("database.connections.{$connection}.database", 'unknown');
        $host = app()->runningInConsole() ? 'cli' : request()->getHost();
        $locale = app()->getLocale();
        ksort($dimensions);

        return 'erp:lookup:v1:' . hash('sha256', json_encode([
            'host' => $host,
            'database' => $database,
            'locale' => $locale,
            'group' => $group,
            'dimensions' => $dimensions,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
