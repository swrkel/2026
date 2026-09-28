<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\Stock\DisnewVehicleStockLedgerService;

class DisnewLoadingService
{
    public function complete(int $loadingId): void
    {
        DB::transaction(function () use ($loadingId) {
            $loading = DB::table('disnew_loadings')->where('id', $loadingId)->lockForUpdate()->first();
            abort_if(!$loading, 404, 'Loading not found');
            abort_if($loading->status === 'completed', 422, 'Loading is already completed.');
            $lines = DB::table('disnew_loading_lines')->where('loading_id', $loadingId)->get();
            foreach ($lines as $line) {
                $loadedQty = (float)($line->loaded_qty ?: $line->qty);
                if ($loadedQty <= 0) { continue; }
                app(DisnewStockService::class)->move($loading->business_id, $loading->location_id, $loading->vehicle_id, $line->product_id, 'in', $loadedQty, 'loading', $loadingId);
                app(DisnewVehicleStockLedgerService::class)->adjustVehicleStore($loading->business_id, $loading->location_id, $loading->store_id ?? null, $loading->vehicle_id, $line->product_id, $loadedQty, 'in', 'loading', $loadingId);
                DB::table('disnew_loading_lines')->where('id', $line->id)->update([
                    'loaded_qty' => $loadedQty,
                    'variance_qty' => $loadedQty - (float)($line->planned_qty ?: $line->qty),
                    'updated_at' => now(),
                ]);
            }
            DB::table('disnew_loadings')->where('id', $loadingId)->update(['status' => 'completed', 'loaded_by' => auth()->id(), 'loaded_at' => now(), 'completed_at' => now(), 'updated_at' => now()]);
            app(DisnewSmsService::class)->queue($loading->business_id, $loading->location_id, 'loading_completed', null, null, 'Distribution loading '.$loading->loading_no.' completed.', ['loading_id' => $loadingId]);
        });
    }
}
