<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class ShiftReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('petro_shifts')
            ->leftJoin('business_locations', 'petro_shifts.location_id', '=', 'business_locations.id')
            ->leftJoin('pump_operators', 'petro_shifts.pump_operator_id', '=', 'pump_operators.id')
            ->where('petro_shifts.business_id', $businessId)
            ->select([
                'petro_shifts.id',
                'petro_shifts.shift_no',
                'petro_shifts.date',
                'business_locations.name as location_name',
                'pump_operators.name as pump_operator_name',
                'petro_shifts.status',
                'petro_shifts.created_at',
            ]);

        $this->applyDateRange($query, 'petro_shifts.date', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $this->applyLocation($query, 'petro_shifts.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('petro_shifts.date', 'desc')->orderBy('petro_shifts.id', 'desc');
    }
}
