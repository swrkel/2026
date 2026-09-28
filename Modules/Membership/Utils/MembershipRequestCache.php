<?php

namespace Modules\Membership\Utils;

/**
 * MEM-015 helper.
 * Lightweight per-request cache for Membership settings/lookups.
 * Safe to use from Membership services to avoid repeated queries during one request.
 */
class MembershipRequestCache
{
    protected static array $items = [];

    public static function remember(string $key, callable $callback)
    {
        if (! array_key_exists($key, static::$items)) {
            static::$items[$key] = $callback();
        }

        return static::$items[$key];
    }

    public static function forget(?string $key = null): void
    {
        if ($key === null) {
            static::$items = [];
            return;
        }

        unset(static::$items[$key]);
    }
}
