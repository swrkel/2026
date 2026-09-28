<?php
namespace Modules\DistributionNew\Services\Stock;
use Illuminate\Support\Facades\DB;

class DisnewBinStockService
{
    public function moveToBin(array $data): void
    {
        DB::table('disnew_bin_stock_movements')->insert([
            'business_id'=>$data['business_id'], 'business_location_id'=>$data['business_location_id'] ?? null,
            'warehouse_id'=>$data['warehouse_id'] ?? null, 'bin_location_id'=>$data['bin_location_id'],
            'product_id'=>$data['product_id'], 'variation_id'=>$data['variation_id'] ?? null,
            'batch_no'=>$data['batch_no'] ?? null, 'qty'=>$data['qty'], 'movement_type'=>$data['movement_type'] ?? 'in',
            'reference_type'=>$data['reference_type'] ?? null, 'reference_id'=>$data['reference_id'] ?? null,
            'created_by'=>$data['created_by'] ?? auth()->id(), 'created_at'=>now(), 'updated_at'=>now()
        ]);
    }

    public function availableQty(int $businessId, int $binId, int $productId): float
    {
        return (float) DB::table('disnew_bin_stock_movements')
            ->where('business_id',$businessId)->where('bin_location_id',$binId)->where('product_id',$productId)
            ->selectRaw("SUM(CASE WHEN movement_type IN ('in','return') THEN qty ELSE -qty END) as bal")
            ->value('bal');
    }
}
