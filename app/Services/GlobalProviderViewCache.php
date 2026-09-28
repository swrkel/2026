<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Short-lived, tenant-safe cache for data resolved by service-provider
 * view composers.
 *
 * Provider composers are executed from many layouts and partials. This
 * service prevents the same database query from being repeated while keeping
 * central and tenant data isolated by host, connection database, business,
 * user and locale.
 */
final class GlobalProviderViewCache
{
    private static array $requestCache = [];

    public static function remember(
        string $name,
        array $context,
        int $seconds,
        callable $resolver
    ): mixed {
        $key = self::key($name, $context);

        if (array_key_exists($key, self::$requestCache)) {
            return self::$requestCache[$key];
        }

        try {
            $value = Cache::remember($key, now()->addSeconds(max(1, $seconds)), $resolver);
        } catch (\Throwable $e) {
            // Provider data must never prevent a page from rendering.
            $value = $resolver();
        }

        return self::$requestCache[$key] = $value;
    }

    public static function forget(string $name, array $context): void
    {
        $key = self::key($name, $context);
        unset(self::$requestCache[$key]);

        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
            // Cache invalidation is best-effort.
        }
    }

    private static function key(string $name, array $context): string
    {
        $host = strtolower((string) request()->getHost());
        $connection = (string) Config::get('database.default', 'mysql');
        $database = (string) Config::get("database.connections.{$connection}.database", '');
        $locale = (string) app()->getLocale();

        $payload = [
            'host' => $host,
            'connection' => $connection,
            'database' => $database,
            'locale' => $locale,
            'context' => array_values($context),
        ];

        return 'erp:provider-view:' . $name . ':' . hash('sha256', json_encode($payload));
    }
}
