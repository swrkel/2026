<?php

namespace Modules\DistributionNew\Services\Stock;

use Illuminate\Support\Facades\DB;

class DisnewVehicleStoreStockService
{
    public function increase(int $businessId, ?int $locationId, ?int $storeId, ?int $vehicleId, int $productId, float $qty, string $referenceType, ?int $referenceId, ?int $userId): void
    {
        $this->adjust($businessId, $locationId, $storeId, $vehicleId, $productId, abs($qty), 'in', $referenceType, $referenceId, $userId);
    }

    public function decrease(int $businessId, ?int $locationId, ?int $storeId, ?int $vehicleId, int $productId, float $qty, string $referenceType, ?int $referenceId, ?int $userId): void
    {
        $this->adjust($businessId, $locationId, $storeId, $vehicleId, $productId, abs($qty), 'out', $referenceType, $referenceId, $userId);
    }

    private function adjust(int $businessId, ?int $locationId, ?int $storeId, ?int $vehicleId, int $productId, float $qty, string $direction, string $referenceType, ?int $referenceId, ?int $userId): void
    {
        DB::transaction(function () use ($businessId, $locationId, $storeId, $vehicleId, $productId, $qty, $direction, $referenceType, $referenceId, $userId) {
            $existing = DB::table('disnew_vehicle_store_stocks')
                ->where('business_id', $businessId)->where('location_id', $locationId)->where('store_id', $storeId)
                ->where('vehicle_id', $vehicleId)->where('product_id', $productId)->lockForUpdate()->first();

            $newQty = ($existing->qty ?? 0) + ($direction === 'in' ? $qty : -$qty);
            if ($newQty < 0) { abort(422, 'Insufficient vehicle/store stock for product '.$productId); }

            if ($existing) {
                DB::table('disnew_vehicle_store_stocks')->where('id', $existing->id)->update(['qty'=>$newQty,'updated_at'=>now()]);
            } else {
                DB::table('disnew_vehicle_store_stocks')->insert([
                    'business_id'=>$businessId,'location_id'=>$locationId,'store_id'=>$storeId,'vehicle_id'=>$vehicleId,'product_id'=>$productId,
                    'qty'=>$newQty,'reserved_qty'=>0,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }

            DB::table('disnew_stock_movements')->insert([
                'business_id'=>$businessId,'location_id'=>$locationId,'vehicle_id'=>$vehicleId,'product_id'=>$productId,
                'direction'=>$direction,'qty'=>$qty,'reference_type'=>$referenceType,'reference_id'=>$referenceId,'created_by'=>$userId,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
}
