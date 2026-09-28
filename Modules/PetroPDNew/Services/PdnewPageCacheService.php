<?php

namespace Modules\PetroPDNew\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PdnewPageCacheService
{
    private const DASHBOARD_TTL_SECONDS = 5;

    public function dashboard(int $businessId, ?int $locationId, Closure $resolver): array
    {
        $key = 'pdnew:page:dashboard:' . sha1(implode('|', [
            $this->databaseName(),
            $businessId,
            $locationId ?: 0,
        ]));

        return Cache::remember($key, now()->addSeconds(self::DASHBOARD_TTL_SECONDS), $resolver);
    }

    public function forgetDashboard(int $businessId, ?int $locationId): void
    {
        $key = 'pdnew:page:dashboard:' . sha1(implode('|', [
            $this->databaseName(),
            $businessId,
            $locationId ?: 0,
        ]));
        Cache::forget($key);
    }

    private function databaseName(): string
    {
        try {
            return (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $exception) {
            return (string) config('database.default', 'tenant');
        }
    }
}
