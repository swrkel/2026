<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyLoyaltyTransaction;

class LoyaltyService
{
    public function customerSummary(int $customerId): array
    {
        $earned = BeautyLoyaltyTransaction::where('customer_id', $customerId)->where('type', 'earn')->sum('points');
        $redeemed = BeautyLoyaltyTransaction::where('customer_id', $customerId)->where('type', 'redeem')->sum('points');

        return [
            'earned' => (float) $earned,
            'redeemed' => (float) $redeemed,
            'balance' => (float) $earned - (float) $redeemed,
        ];
    }

    public function earn(array $data): BeautyLoyaltyTransaction
    {
        return DB::transaction(function () use ($data) {
            return BeautyLoyaltyTransaction::create([
                'business_id' => $data['business_id'] ?? request()->session()->get('user.business_id'),
                'business_location_id' => $data['business_location_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'type' => 'earn',
                'points' => abs((float) $data['points']),
                'amount_value' => $data['amount_value'] ?? 0,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function redeem(array $data): BeautyLoyaltyTransaction
    {
        return DB::transaction(function () use ($data) {
            return BeautyLoyaltyTransaction::create([
                'business_id' => $data['business_id'] ?? request()->session()->get('user.business_id'),
                'business_location_id' => $data['business_location_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'reference_type' => $data['reference_type'] ?? 'manual',
                'reference_id' => $data['reference_id'] ?? null,
                'type' => 'redeem',
                'points' => abs((float) $data['points']),
                'amount_value' => $data['amount_value'] ?? 0,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }
}
