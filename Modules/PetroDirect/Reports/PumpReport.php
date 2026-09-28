<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class PumpReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('pumps')
            ->leftJoin('business_locations', 'pumps.location_id', '=', 'business_locations.id')
            ->leftJoin('fuel_tanks', 'pumps.fuel_tank_id', '=', 'fuel_tanks.id')
            ->where('pumps.business_id', $businessId)
            ->select([
                'pumps.id',
                'pumps.pump_name',
                'pumps.pump_no',
                'pumps.starting_meter',
                'pumps.current_meter',
                'business_locations.name as location_name',
                'fuel_tanks.fuel_tank_number',
                'pumps.created_at',
            ]);

        $this->applyLocation($query, 'pumps.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('pumps.pump_no');
    }
}
