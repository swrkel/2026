<?php
namespace Modules\AirlineTicketingNew\Services\Cache;

use Illuminate\Support\Facades\Cache;

class ModuleCacheService
{
    public function remember(int $businessId, string $key, int $seconds, \Closure $callback): mixed
    {
        return Cache::remember($this->key($businessId, $key), $seconds, $callback);
    }

    public function forget(int $businessId, string $key): void
    {
        Cache::forget($this->key($businessId, $key));
    }

    public function flushBusiness(int $businessId, array $knownKeys): void
    {
        foreach ($knownKeys as $key) {
            $this->forget($businessId, $key);
        }
    }

    private function key(int $businessId, string $key): string
    {
        return 'atn:' . $businessId . ':' . $key;
    }
}
