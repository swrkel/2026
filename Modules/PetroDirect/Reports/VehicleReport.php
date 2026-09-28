<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class VehicleReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        return DB::table('vehicles')
            ->where('business_id', $businessId)
            ->select(['id', 'vehicle_no', 'vehicle_name', 'created_at'])
            ->orderBy('vehicle_no');
    }
}
