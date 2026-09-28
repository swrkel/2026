<?php

namespace Modules\BankingMicrofinance\Services;

use Illuminate\Support\Facades\DB;
use Modules\BankingMicrofinance\Entities\RepaymentAllocation;

class RepaymentAllocationService
{
    public function preview(float $received, float $fees=0, float $penalties=0, float $interest=0, float $principal=0): array
    {
        $remaining = round($received, 4);
        $feePaid = min($remaining, $fees); $remaining -= $feePaid;
        $penaltyPaid = min($remaining, $penalties); $remaining -= $penaltyPaid;
        $interestPaid = min($remaining, $interest); $remaining -= $interestPaid;
        $principalPaid = min($remaining, $principal); $remaining -= $principalPaid;
        return [
            'received_amount'=>$received,
            'fee_amount'=>round($feePaid,4),
            'penalty_amount'=>round($penaltyPaid,4),
            'interest_amount'=>round($interestPaid,4),
            'principal_amount'=>round($principalPaid,4),
            'unallocated_amount'=>round(max($remaining,0),4),
        ];
    }

    public function post(array $data): RepaymentAllocation
    {
        return DB::transaction(function () use ($data) {
            $preview = $this->preview((float)$data['received_amount'], (float)($data['fee_due']??0), (float)($data['penalty_due']??0), (float)($data['interest_due']??0), (float)($data['principal_due']??0));
            return RepaymentAllocation::create(array_merge($preview, [
                'loan_id'=>$data['loan_id'],
                'collection_id'=>$data['collection_id']??null,
                'allocation_date'=>$data['allocation_date']??now()->toDateString(),
                'created_by'=>auth()->id(),
            ]));
        });
    }
}
