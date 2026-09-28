<?php

namespace Modules\PetroDirect\Reports;

use Illuminate\Support\Facades\DB;

class TankReport extends BaseReport
{
    public function summary(array $filters = [])
    {
        $businessId = $this->businessId($filters['business_id'] ?? null);

        $query = DB::table('fuel_tanks')
            ->leftJoin('business_locations', 'fuel_tanks.location_id', '=', 'business_locations.id')
            ->leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
            ->where('fuel_tanks.business_id', $businessId)
            ->select([
                'fuel_tanks.id',
                'fuel_tanks.fuel_tank_number',
                'fuel_tanks.tank_manufacturer',
                'fuel_tanks.storage_volume',
                'fuel_tanks.current_balance',
                'business_locations.name as location_name',
                'products.name as product_name',
                'fuel_tanks.created_at',
            ]);

        $this->applyLocation($query, 'fuel_tanks.location_id', $filters['location_id'] ?? null);

        return $query->orderBy('fuel_tanks.fuel_tank_number');
    }
}
