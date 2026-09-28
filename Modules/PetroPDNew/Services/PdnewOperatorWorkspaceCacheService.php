<?php

namespace Modules\PetroPDNew\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PdnewOperatorWorkspaceCacheService
{
    private const TTL_SECONDS = 8;

    /** @param array<string,mixed> $filters */
    public function remember(int $businessId, ?int $locationId, string $tab, array $filters, Closure $resolver): array
    {
        $version = (int) Cache::get($this->versionKey($businessId), 1);
        $payload = [
            'database' => $this->databaseName(),
            'business_id' => $businessId,
            'location_id' => $locationId ?: 0,
            'tab' => $tab,
            'filters' => $filters,
            'version' => $version,
        ];
        $key = 'pdnew:operator-workspace:data:' . sha1(json_encode($payload));

        return Cache::remember($key, now()->addSeconds(self::TTL_SECONDS), $resolver);
    }

    public function flush(int $businessId): void
    {
        $key = $this->versionKey($businessId);
        if (! Cache::has($key)) {
            Cache::forever($key, 2);
            return;
        }

        try {
            Cache::increment($key);
        } catch (\Throwable $exception) {
            Cache::forever($key, ((int) Cache::get($key, 1)) + 1);
        }
    }

    private function versionKey(int $businessId): string
    {
        return 'pdnew:operator-workspace:version:' . sha1($this->databaseName() . '|' . $businessId);
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
