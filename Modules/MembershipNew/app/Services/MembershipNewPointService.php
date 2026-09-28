<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewPointRule;
use Modules\MembershipNew\app\Models\MembershipNewPointTransaction;

class MembershipNewPointService
{
    public function balance(int $businessId, int $memberId): float
    {
        return (float) MembershipNewPointTransaction::forBusiness($businessId)
            ->where('member_id', $memberId)
            ->sum('points');
    }

    public function previewEarnPoints(int $businessId, ?int $outletBusinessId, ?int $locationId, ?int $categoryId, float $purchaseAmount): float
    {
        $rule = MembershipNewPointRule::forBusiness($businessId)
            ->where('is_active', 1)
            ->where(function ($q) use ($outletBusinessId) {
                $q->whereNull('outlet_business_id')->orWhere('outlet_business_id', $outletBusinessId);
            })
            ->where(function ($q) use ($locationId) {
                $q->whereNull('location_id')->orWhere('location_id', $locationId);
            })
            ->where(function ($q) use ($categoryId) {
                $q->whereNull('category_id')->orWhere('category_id', $categoryId);
            })
            ->orderByRaw('outlet_business_id IS NULL ASC')
            ->orderByRaw('location_id IS NULL ASC')
            ->orderByRaw('category_id IS NULL ASC')
            ->first();

        if (!$rule || $purchaseAmount <= 0) {
            return 0.0;
        }

        $step = max((float) $rule->amount_step, 0.0001);
        $points = floor($purchaseAmount / $step) * (float) $rule->points_per_amount;

        if ($rule->max_points_per_invoice !== null) {
            $points = min($points, (float) $rule->max_points_per_invoice);
        }

        return round($points, 4);
    }

    public function earn(array $data): MembershipNewPointTransaction
    {
        return DB::transaction(function () use ($data) {
            $data['type'] = 'earn';
            $data['points'] = abs((float) $data['points']);
            $data['transaction_date'] = $data['transaction_date'] ?? now();
            return MembershipNewPointTransaction::create($data);
        });
    }

    public function redeem(array $data): MembershipNewPointTransaction
    {
        return DB::transaction(function () use ($data) {
            $points = abs((float) $data['points']);
            $available = $this->balance((int) $data['business_id'], (int) $data['member_id']);

            if ($points > $available) {
                throw new \RuntimeException('Insufficient membership points. Available balance: ' . number_format($available, 4));
            }

            $data['type'] = 'redeem';
            $data['points'] = -1 * $points;
            $data['transaction_date'] = $data['transaction_date'] ?? now();

            return MembershipNewPointTransaction::create($data);
        });
    }
}
