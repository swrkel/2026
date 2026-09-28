<?php

namespace Modules\DistributionNew\Services\ProductionCompletion;

use Illuminate\Support\Facades\DB;

class PerformanceCacheService
{
    public function remember(int $businessId, string $key, callable $callback, int $minutes = 15)
    {
        $row = DB::table('disnew_performance_cache')
            ->where('business_id', $businessId)
            ->where('cache_key', $key)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
            ->first();

        if ($row) {
            return json_decode($row->cache_payload, true);
        }

        $value = $callback();
        DB::table('disnew_performance_cache')->updateOrInsert(
            ['business_id' => $businessId, 'cache_key' => $key],
            ['cache_payload' => json_encode($value), 'expires_at' => now()->addMinutes($minutes), 'updated_at' => now(), 'created_at' => now()]
        );
        return $value;
    }
}
