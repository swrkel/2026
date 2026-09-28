<?php
namespace Modules\ProductsNew\Services\Batch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewBatch;
use Modules\ProductsNew\Entities\ProductsNewBatchMovement;
use Modules\ProductsNew\Services\ProductStatusService;
use Modules\ProductsNew\Services\ProductTimelineService;

class BatchService
{
    public function __construct(protected ProductStatusService $status)
    {
    }

    public function query(array $filters = [])
    {
        $businessId = $filters['business_id'] ?? session('business.id');
        return ProductsNewBatch::query()
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->when(!empty($filters['product_id']), fn($q) => $q->where('product_id', $filters['product_id']))
            ->when(!empty($filters['location_id']), fn($q) => $q->where('location_id', $filters['location_id']))
            ->when(!empty($filters['batch_no']), fn($q) => $q->where('batch_no', 'like', '%'.$filters['batch_no'].'%'))
            ->when(!empty($filters['expiry_status']), function($q) use ($filters) {
                if ($filters['expiry_status'] === 'expired') $q->whereDate('expiry_at', '<', now()->toDateString());
                if ($filters['expiry_status'] === 'expiring_soon') $q->whereBetween('expiry_at', [now()->toDateString(), now()->addDays(30)->toDateString()]);
                if ($filters['expiry_status'] === 'valid') $q->whereDate('expiry_at', '>=', now()->toDateString());
            })
            ->orderByRaw('CASE WHEN expiry_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expiry_at')
            ->latest('id');
    }

    public function create(array $data): ProductsNewBatch
    {
        $this->status->assertActiveProductId((int) $data['product_id']);

        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['current_qty'] = $data['current_qty'] ?? ($data['opening_qty'] ?? 0);
        $data['available_qty'] = $data['available_qty'] ?? $data['current_qty'];
        $batch = ProductsNewBatch::create($data);
        if ((float)($batch->current_qty ?? 0) > 0) {
            $this->movement($batch, 'opening', (float)$batch->current_qty, 0, 'Opening batch balance');
        }
        app(ProductTimelineService::class)->record($batch->product_id, 'batch_created', 'Batch '.$batch->batch_no.' created', $batch->toArray());
        return $batch;
    }

    public function movement(ProductsNewBatch $batch, string $type, float $qtyIn = 0, float $qtyOut = 0, ?string $note = null): ProductsNewBatchMovement
    {
        $balance = (float)$batch->current_qty + $qtyIn - $qtyOut;
        $movement = ProductsNewBatchMovement::create([
            'business_id' => $batch->business_id,
            'product_id' => $batch->product_id,
            'variation_id' => $batch->variation_id,
            'location_id' => $batch->location_id,
            'batch_id' => $batch->id,
            'movement_type' => $type,
            'transaction_date' => now(),
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance_after' => $balance,
            'reference_type' => request('reference_type'),
            'reference_id' => request('reference_id'),
            'note' => $note,
            'created_by' => auth()->id(),
        ]);
        $batch->update(['current_qty' => $balance, 'available_qty' => max(0, $balance - (float)$batch->reserved_qty)]);
        return $movement;
    }

    public function consumeFefo(int $productId, int $locationId, float $qty, ?int $variationId = null): array
    {
        $remaining = $qty;
        $used = [];
        $batches = ProductsNewBatch::query()
            ->where('product_id', $productId)->where('location_id', $locationId)
            ->when($variationId, fn($q)=>$q->where('variation_id',$variationId))
            ->where('available_qty', '>', 0)
            ->orderByRaw('CASE WHEN expiry_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('expiry_at')->orderBy('id')->get();
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($remaining, (float)$batch->available_qty);
            $this->movement($batch, 'fefo_consumed', 0, $take, 'FEFO allocation');
            $used[] = ['batch_id'=>$batch->id, 'batch_no'=>$batch->batch_no, 'qty'=>$take];
            $remaining -= $take;
        }
        return ['requested'=>$qty, 'allocated'=>$qty-$remaining, 'remaining'=>$remaining, 'batches'=>$used];
    }

    public function movements(array $filters = [])
    {
        $businessId = $filters['business_id'] ?? session('business.id');
        return ProductsNewBatchMovement::query()
            ->when($businessId, fn($q)=>$q->where('business_id',$businessId))
            ->when(!empty($filters['batch_id']), fn($q)=>$q->where('batch_id',$filters['batch_id']))
            ->when(!empty($filters['product_id']), fn($q)=>$q->where('product_id',$filters['product_id']))
            ->latest('transaction_date')->latest('id');
    }
}
