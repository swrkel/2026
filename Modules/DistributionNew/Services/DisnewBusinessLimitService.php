<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewBusinessLimit;

class DisnewBusinessLimitService
{
    public function assertLimit(int $businessId, string $key, string $table, ?string $activeColumn = null): void
    {
        $limit = DisnewBusinessLimit::where('business_id', $businessId)->value($key);
        if (!$limit) { return; }
        $query = DB::table($table)->where('business_id', $businessId);
        if ($activeColumn) { $query->where($activeColumn, 1); }
        if ($query->count() >= (int)$limit) {
            throw new \RuntimeException('Distribution New business limit reached for '.$key.'.');
        }
    }
}
