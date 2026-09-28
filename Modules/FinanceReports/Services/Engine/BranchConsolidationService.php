<?php

namespace Modules\FinanceReports\Services\Engine;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchConsolidationService
{
    public function locations(int $business_id): Collection
    {
        if (!Schema::hasTable('business_locations')) {
            return collect();
        }

        return DB::table('business_locations')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    public function normalize($location_id)
    {
        return empty($location_id) || $location_id === 'all' ? null : $location_id;
    }

    public function isConsolidated($location_id): bool
    {
        return $this->normalize($location_id) === null;
    }
}
