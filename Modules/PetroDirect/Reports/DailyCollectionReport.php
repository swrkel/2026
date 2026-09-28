<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class DailyCollectionReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('daily_collections')
            ->leftJoin('business_locations', 'daily_collections.location_id', '=', 'business_locations.id')
            ->leftJoin('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
            ->where('daily_collections.business_id', $businessId)
            ->select([
                'daily_collections.id',
                'daily_collections.collection_form_no',
                'daily_collections.collection_date',
                'business_locations.name as location_name',
                'settlements.settlement_no',
                'daily_collections.amount',
                'daily_collections.created_at',
            ]);

        $this->applyDateRange($query, 'daily_collections.collection_date', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $this->applyLocation($query, 'daily_collections.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('daily_collections.collection_date', 'desc')->orderBy('daily_collections.id', 'desc');
    }
}
