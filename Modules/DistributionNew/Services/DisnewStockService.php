<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;

class DisnewStockService
{
    public function move(int $businessId, ?int $locationId, ?int $vehicleId, int $productId, string $direction, float $qty, string $refType, int $refId): void
    {
        DB::table('disnew_stock_movements')->insert([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'vehicle_id' => $vehicleId,
            'product_id' => $productId,
            'direction' => $direction,
            'qty' => $qty,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stock = DB::table('disnew_vehicle_stocks')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where('vehicle_id', $vehicleId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $newQty = (float) $stock->qty + ($direction === 'out' ? -$qty : $qty);
            DB::table('disnew_vehicle_stocks')->where('id', $stock->id)->update(['qty' => $newQty, 'updated_at' => now()]);
        } else {
            DB::table('disnew_vehicle_stocks')->insert([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'vehicle_id' => $vehicleId,
                'product_id' => $productId,
                'qty' => $direction === 'out' ? -$qty : $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
