<?php

namespace Modules\PetroGeneral\Services\Tank;

use Illuminate\Support\Facades\DB;

class TankListService
{
    public function getTanks(int $businessId)
    {
        return DB::table('fuel_tanks')
            ->leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
            ->where('fuel_tanks.business_id', $businessId)
            ->whereNull('fuel_tanks.deleted_at')
            ->select('fuel_tanks.*', 'products.name as product_name')
            ->orderBy('fuel_tanks.fuel_tank_number')
            ->get();
    }

    public function getRecentTransfers(int $businessId)
    {
        return DB::table('tank_transfers')
            ->where('business_id', $businessId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    public function getTransactionSummary(int $businessId)
    {
        return DB::table('tanks_transaction_details')
            ->where('business_id', $businessId)
            ->select('tank_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('tank_id')
            ->limit(50)
            ->get();
    }
}
