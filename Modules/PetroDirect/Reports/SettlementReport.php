<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class SettlementReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('settlements')
            ->leftJoin('business_locations', 'settlements.location_id', '=', 'business_locations.id')
            ->leftJoin('pump_operators', 'settlements.pump_operator_id', '=', 'pump_operators.id')
            ->where('settlements.business_id', $businessId)
            ->select([
                'settlements.id',
                'settlements.settlement_no',
                'settlements.transaction_date',
                'business_locations.name as location_name',
                'pump_operators.name as pump_operator_name',
                'settlements.status',
                'settlements.total_amount',
                'settlements.created_at',
            ]);

        $this->applyDateRange($query, 'settlements.transaction_date', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $this->applyLocation($query, 'settlements.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('settlements.transaction_date', 'desc')->orderBy('settlements.id', 'desc');
    }
}
