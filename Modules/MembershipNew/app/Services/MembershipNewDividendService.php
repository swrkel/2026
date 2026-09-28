<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewDividendBatch;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayment;
use Modules\MembershipNew\app\Models\MembershipNewShareHolding;

class MembershipNewDividendService
{
    public function preview(int $businessId, float $totalAmount): array
    {
        $totalShares = (float) MembershipNewShareHolding::forBusiness($businessId)->sum('shares');
        return [
            'total_shares' => $totalShares,
            'dividend_per_share' => $totalShares > 0 ? round($totalAmount / $totalShares, 6) : 0,
            'total_amount' => $totalAmount,
        ];
    }

    public function createBatch(int $businessId, array $data): MembershipNewDividendBatch
    {
        return DB::transaction(function () use ($businessId, $data) {
            $preview = $this->preview($businessId, (float) $data['total_dividend_amount']);

            $batch = MembershipNewDividendBatch::create([
                'business_id' => $businessId,
                'dividend_date' => $data['dividend_date'] ?? now()->toDateString(),
                'total_dividend_amount' => (float) $data['total_dividend_amount'],
                'dividend_per_share' => $preview['dividend_per_share'],
                'note' => $data['note'] ?? null,
                'is_posted' => 0,
            ]);

            MembershipNewShareHolding::forBusiness($businessId)->chunkById(100, function ($holdings) use ($batch) {
                foreach ($holdings as $holding) {
                    MembershipNewDividendPayment::create([
                        'business_id' => $holding->business_id,
                        'batch_id' => $batch->id,
                        'member_id' => $holding->member_id,
                        'shares' => $holding->shares,
                        'amount' => round(((float) $holding->shares) * ((float) $batch->dividend_per_share), 4),
                        'is_paid' => 0,
                    ]);
                }
            });

            return $batch->fresh('payments');
        });
    }

    public function post(MembershipNewDividendBatch $batch): MembershipNewDividendBatch
    {
        $batch->update([
            'is_posted' => 1,
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        return $batch->fresh();
    }
}
