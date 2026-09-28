<?php

namespace Modules\DistributionNew\Services\Settlements;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewCollection;
use Modules\DistributionNew\Models\DisnewSettlement;
use Modules\DistributionNew\Models\DisnewSettlementLine;
use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;

class DisnewSettlementService
{
    public function listForBusiness(int $businessId, array $filters = [])
    {
        return DisnewSettlement::query()
            ->where('business_id', $businessId)
            ->when($filters['business_location_id'] ?? null, fn($q,$v)=>$q->where('business_location_id',$v))
            ->when($filters['sales_rep_id'] ?? null, fn($q,$v)=>$q->where('sales_rep_id',$v))
            ->when($filters['vehicle_id'] ?? null, fn($q,$v)=>$q->where('vehicle_id',$v))
            ->latest('id');
    }

    public function createDraft(array $data): DisnewSettlement
    {
        return DB::transaction(function () use ($data) {
            $data['settlement_no'] = $data['settlement_no'] ?? $this->nextNo((int)$data['business_id']);
            $data['collection_total'] = $this->collectionTotal($data);
            $settlement = DisnewSettlement::create($data + ['status' => 'draft']);
            $this->syncSummaryLines($settlement);
            return $settlement->fresh('lines');
        });
    }

    public function finalize(DisnewSettlement $settlement, int $userId): DisnewSettlement
    {
        return DB::transaction(function () use ($settlement, $userId) {
            $settlement->update(['status'=>'finalized','finalized_by'=>$userId,'finalized_at'=>now()]);
            app(DisnewSmsEventService::class)->queueSettlementFinalized($settlement);
            return $settlement->fresh('lines');
        });
    }

    protected function collectionTotal(array $data): float
    {
        return (float) DisnewCollection::where('business_id', $data['business_id'])
            ->when($data['business_location_id'] ?? null, fn($q,$v)=>$q->where('business_location_id',$v))
            ->when($data['sales_rep_id'] ?? null, fn($q,$v)=>$q->where('sales_rep_id',$v))
            ->whereDate('collection_date', $data['settlement_date'])
            ->where('status', 'confirmed')
            ->sum('amount');
    }

    protected function syncSummaryLines(DisnewSettlement $settlement): void
    {
        $lines = [
            ['line_type'=>'loaded','description'=>'Loaded value','amount'=>$settlement->loaded_value],
            ['line_type'=>'sold','description'=>'Sold value','amount'=>$settlement->sold_value],
            ['line_type'=>'returned','description'=>'Returned value','amount'=>$settlement->returned_value],
            ['line_type'=>'collections','description'=>'Confirmed collections','amount'=>$settlement->collection_total],
            ['line_type'=>'shortage','description'=>'Shortage value','amount'=>$settlement->shortage_value],
            ['line_type'=>'excess','description'=>'Excess value','amount'=>$settlement->excess_value],
        ];
        foreach ($lines as $line) {
            DisnewSettlementLine::create($line + ['business_id'=>$settlement->business_id,'settlement_id'=>$settlement->id,'qty'=>0]);
        }
    }

    protected function nextNo(int $businessId): string
    {
        $next = (int) DisnewSettlement::where('business_id',$businessId)->max('id') + 1;
        return 'DNSET-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
    }
}
