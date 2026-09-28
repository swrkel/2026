<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\Stock\DisnewVehicleStockLedgerService;

class DisnewUnloadingService
{
    public function complete(int $unloadingId): void
    {
        DB::transaction(function () use ($unloadingId) {
            $unloading = DB::table('disnew_unloadings')->where('id', $unloadingId)->lockForUpdate()->first();
            abort_if(!$unloading, 404, 'Unloading not found');
            abort_if($unloading->status === 'completed', 422, 'Unloading is already completed.');
            $lines = DB::table('disnew_unloading_lines')->where('unloading_id', $unloadingId)->get();
            foreach ($lines as $line) {
                $returnQty = (float)$line->returned_qty + (float)$line->damaged_qty;
                $deliveredQty = (float)$line->delivered_qty;
                $outQty = $returnQty + $deliveredQty;
                if ($outQty > 0) {
                    app(DisnewStockService::class)->move($unloading->business_id, $unloading->location_id, $unloading->vehicle_id, $line->product_id, 'out', $outQty, 'unloading', $unloadingId);
                    app(DisnewVehicleStockLedgerService::class)->adjustVehicleStore($unloading->business_id, $unloading->location_id, $unloading->store_id ?? null, $unloading->vehicle_id, $line->product_id, $outQty, 'out', 'unloading', $unloadingId);
                }
                if ((float)$line->shortage_qty != 0 || (float)$line->damaged_qty != 0) {
                    DB::table('disnew_delivery_issue_logs')->insert([
                        'business_id' => $unloading->business_id,
                        'location_id' => $unloading->location_id,
                        'sales_order_id' => $line->sales_order_id,
                        'unloading_id' => $unloadingId,
                        'vehicle_id' => $unloading->vehicle_id,
                        'issue_type' => (float)$line->damaged_qty > 0 ? 'damage' : 'short_delivery',
                        'product_id' => $line->product_id,
                        'qty' => max((float)$line->damaged_qty, (float)$line->shortage_qty),
                        'note' => $line->note,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            DB::table('disnew_unloadings')->where('id', $unloadingId)->update(['status' => 'completed', 'unloaded_by' => auth()->id(), 'unloaded_at' => now(), 'completed_at' => now(), 'updated_at' => now()]);
            app(DisnewSmsService::class)->queue($unloading->business_id, $unloading->location_id, 'unloading_completed', null, null, 'Distribution unloading completed.', ['unloading_id' => $unloadingId]);
        });
    }
}
