<?php

namespace App\Services;

use App\Utils\SidebarPermissionUtil;
use Illuminate\Support\Facades\Cache;

class GlobalPerformanceInvalidator
{
    public static function business(int $businessId): void
    {
        SidebarPermissionUtil::forgetBusinessCache($businessId);
        Cache::forget('gpo:subscription:' . $businessId);
        Cache::forget('gpo:business:' . $businessId);
    }

    public static function permissions(int $businessId, ?int $userId = null): void
    {
        self::business($businessId);
        if ($userId) {
            Cache::forget("gpo:permissions:{$businessId}:{$userId}");
        }
    }

    public static function schema(?string $connection = null): void
    {
        GlobalSchemaCache::forget($connection);
    }

    public static function modules(): void
    {
        GlobalModuleRegistryCache::forget();
    }
}
