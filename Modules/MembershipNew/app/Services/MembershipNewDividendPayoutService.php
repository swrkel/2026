<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayment;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayout;

class MembershipNewDividendPayoutService
{
    public function pay(int $businessId, int $dividendPaymentId, array $data): MembershipNewDividendPayout
    {
        return DB::transaction(function () use ($businessId, $dividendPaymentId, $data) {
            $payment = MembershipNewDividendPayment::where('business_id', $businessId)->findOrFail($dividendPaymentId);

            $payout = MembershipNewDividendPayout::create([
                'business_id' => $businessId,
                'dividend_payment_id' => $payment->id,
                'member_id' => $payment->member_id,
                'central_member_id' => $payment->central_member_id ?? null,
                'member_business_map_id' => $payment->member_business_map_id ?? null,
                'amount' => $data['amount'] ?? $payment->amount,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_ref_no' => $data['payment_ref_no'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $payment->update([
                'is_paid' => 1,
                'paid_at' => $payout->paid_at,
            ]);

            return $payout;
        });
    }

    public function reverse(int $businessId, int $payoutId, ?string $note = null): MembershipNewDividendPayout
    {
        return DB::transaction(function () use ($businessId, $payoutId, $note) {
            $payout = MembershipNewDividendPayout::forBusiness($businessId)->findOrFail($payoutId);
            $payout->update([
                'is_reversed' => 1,
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
                'reversal_note' => $note,
            ]);

            return $payout->fresh();
        });
    }
}
