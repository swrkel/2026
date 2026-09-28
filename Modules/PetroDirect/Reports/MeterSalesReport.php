<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class MeterSalesReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('meter_sales')
            ->leftJoin('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->leftJoin('business_locations', 'meter_sales.location_id', '=', 'business_locations.id')
            ->where('meter_sales.business_id', $businessId)
            ->select([
                'meter_sales.id',
                'meter_sales.date',
                'pumps.pump_name',
                'pumps.pump_no',
                'business_locations.name as location_name',
                'meter_sales.starting_meter',
                'meter_sales.closing_meter',
                'meter_sales.sold_ltr',
                'meter_sales.amount',
                'meter_sales.created_at',
            ]);

        $this->applyDateRange($query, 'meter_sales.date', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $this->applyLocation($query, 'meter_sales.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('meter_sales.date', 'desc')->orderBy('meter_sales.id', 'desc');
    }
}
