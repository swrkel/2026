<?php

namespace Modules\DistributionNew\Services\Stock;

use Illuminate\Support\Facades\DB;

class DisnewVehicleStockLedgerService
{
    public function adjustVehicleStore(int $businessId, ?int $locationId, ?int $storeId, ?int $vehicleId, int $productId, float $qty, string $direction, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $signQty = $direction === 'out' ? -abs($qty) : abs($qty);
        $existing = DB::table('disnew_vehicle_store_stocks')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('store_id', $storeId)
            ->where('vehicle_id', $vehicleId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();
        if ($existing) {
            DB::table('disnew_vehicle_store_stocks')->where('id', $existing->id)->update(['qty' => DB::raw('qty + '.(float)$signQty), 'updated_at' => now()]);
        } else {
            DB::table('disnew_vehicle_store_stocks')->insert([
                'business_id' => $businessId, 'location_id' => $locationId, 'store_id' => $storeId, 'vehicle_id' => $vehicleId,
                'product_id' => $productId, 'qty' => $signQty, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('disnew_stock_movements')->insert([
            'business_id' => $businessId, 'location_id' => $locationId, 'vehicle_id' => $vehicleId,
            'product_id' => $productId, 'direction' => $direction, 'qty' => abs($qty),
            'reference_type' => $referenceType, 'reference_id' => $referenceId, 'created_by' => auth()->id(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
