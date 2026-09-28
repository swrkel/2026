<?php
namespace Modules\AirlineTicketingNew\Services\Cache;

use Illuminate\Support\Facades\DB;

class LookupCacheService
{
    public function __construct(private readonly ModuleCacheService $cache)
    {
    }

    public function airlines(int $businessId)
    {
        return $this->cache->remember($businessId, 'lookups:airlines', 900, fn () =>
            DB::table('atn_airlines')
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function airports(int $businessId)
    {
        return $this->cache->remember($businessId, 'lookups:airports', 900, fn () =>
            DB::table('atn_airports')
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->orderBy('iata_code')
                ->get()
        );
    }
}
