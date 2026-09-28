<?php

namespace Modules\PetroGeneral\Services\Pump;

use Illuminate\Support\Facades\DB;

class PumpListService
{
    public function getPumps(int $businessId)
    {
        return DB::table('pumps')
            ->leftJoin('products', 'pumps.product_id', '=', 'products.id')
            ->where('pumps.business_id', $businessId)
            ->whereNull('pumps.deleted_at')
            ->select('pumps.*', 'products.name as product_name')
            ->orderBy('pumps.pump_no')
            ->get();
    }

    public function getRecentMeterReadings(int $businessId)
    {
        return DB::table('pump_operator_meter_sales')
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function getRecentTestingDetails(int $businessId)
    {
        return DB::table('meter_resettings')
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }
}
