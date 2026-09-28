<?php
namespace Modules\ProductsNew\Services\Batch;

use Modules\ProductsNew\Entities\ProductsNewRecall;
use Modules\ProductsNew\Services\ProductStatusService;
use Modules\ProductsNew\Services\ProductTimelineService;

class RecallService
{
    public function __construct(protected ProductStatusService $status)
    {
    }

    public function query(array $filters = [])
    {
        $businessId = $filters['business_id'] ?? session('business.id');
        return ProductsNewRecall::query()
            ->when($businessId, fn($q)=>$q->where('business_id',$businessId))
            ->when(!empty($filters['status']), fn($q)=>$q->where('status',$filters['status']))
            ->latest('id');
    }

    public function create(array $data): ProductsNewRecall
    {
        $this->status->assertActiveProductId((int) $data['product_id']);

        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['status'] = $data['status'] ?? 'open';
        $data['started_at'] = $data['started_at'] ?? now();
        $data['created_by'] = auth()->id();
        $recall = ProductsNewRecall::create($data);
        app(ProductTimelineService::class)->record($recall->product_id, 'batch_recall_opened', 'Recall opened: '.$recall->recall_no, $recall->toArray());
        return $recall;
    }

    public function close(int $id): void
    {
        $recall = ProductsNewRecall::findOrFail($id);
        $recall->update(['status'=>'closed','closed_at'=>now(),'closed_by'=>auth()->id()]);
        app(ProductTimelineService::class)->record($recall->product_id, 'batch_recall_closed', 'Recall closed: '.$recall->recall_no, $recall->toArray());
    }
}
