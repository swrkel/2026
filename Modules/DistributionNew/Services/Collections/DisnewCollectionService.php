<?php

namespace Modules\DistributionNew\Services\Collections;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewCollection;
use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;

class DisnewCollectionService
{
    public function listForBusiness(int $businessId, array $filters = [])
    {
        return DisnewCollection::query()
            ->where('business_id', $businessId)
            ->when($filters['business_location_id'] ?? null, fn($q,$v)=>$q->where('business_location_id',$v))
            ->when($filters['sales_rep_id'] ?? null, fn($q,$v)=>$q->where('sales_rep_id',$v))
            ->when($filters['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))
            ->latest('id');
    }

    public function store(array $data): DisnewCollection
    {
        return DB::transaction(function () use ($data) {
            $data['collection_no'] = $data['collection_no'] ?? $this->nextNo((int)$data['business_id']);
            $collection = DisnewCollection::create($data);
            app(DisnewSmsEventService::class)->queueCollectionReceived($collection);
            return $collection;
        });
    }

    public function confirm(DisnewCollection $collection, int $userId): DisnewCollection
    {
        $collection->update(['status' => 'confirmed', 'updated_by' => $userId]);
        app(DisnewSmsEventService::class)->queueCollectionConfirmed($collection);
        return $collection;
    }

    protected function nextNo(int $businessId): string
    {
        $next = (int) DisnewCollection::where('business_id',$businessId)->max('id') + 1;
        return 'DNCOL-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}
