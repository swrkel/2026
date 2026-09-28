<?php

namespace Modules\DistributionNew\Reports;

use Illuminate\Support\Facades\DB;

class DisnewVehicleStoreStockReport
{
    public function rows(int $businessId, ?int $locationId = null)
    {
        return DB::table('disnew_vehicle_store_stocks')
            ->where('business_id', $businessId)
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->orderBy('store_id')
            ->orderBy('vehicle_id')
            ->orderBy('product_id')
            ->get();
    }
}
