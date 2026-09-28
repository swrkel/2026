<?php
namespace Modules\ProductsNew\Services\Batch;

use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewExpiryAlert;

class ExpiryAlertService
{
    public function refresh(?int $businessId = null, int $days = 30): int
    {
        $businessId = $businessId ?: session('business.id');
        $batches = DB::table('products_new_batches')
            ->where('business_id', $businessId)
            ->whereNotNull('expiry_at')
            ->where('current_qty', '>', 0)
            ->whereDate('expiry_at', '<=', now()->addDays($days)->toDateString())
            ->get();
        $count = 0;
        foreach ($batches as $batch) {
            $severity = now()->toDateString() > $batch->expiry_at ? 'expired' : 'warning';
            ProductsNewExpiryAlert::updateOrCreate(
                ['business_id'=>$batch->business_id, 'batch_id'=>$batch->id, 'alert_type'=>$severity],
                ['product_id'=>$batch->product_id, 'variation_id'=>$batch->variation_id, 'location_id'=>$batch->location_id, 'batch_no'=>$batch->batch_no, 'expiry_at'=>$batch->expiry_at, 'alert_date'=>now()->toDateString(), 'severity'=>$severity, 'is_resolved'=>0]
            );
            $count++;
        }
        return $count;
    }

    public function query(array $filters = [])
    {
        $businessId = $filters['business_id'] ?? session('business.id');
        return ProductsNewExpiryAlert::query()
            ->when($businessId, fn($q)=>$q->where('business_id',$businessId))
            ->when(isset($filters['is_resolved']), fn($q)=>$q->where('is_resolved',(bool)$filters['is_resolved']))
            ->when(!empty($filters['severity']), fn($q)=>$q->where('severity',$filters['severity']))
            ->orderBy('expiry_at')->latest('id');
    }

    public function resolve(int $id): void
    {
        ProductsNewExpiryAlert::where('id',$id)->update(['is_resolved'=>1,'resolved_at'=>now(),'resolved_by'=>auth()->id()]);
    }
}
