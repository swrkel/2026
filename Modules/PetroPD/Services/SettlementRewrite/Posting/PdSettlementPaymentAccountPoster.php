<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment account-book posting seam for the PD settlement rewrite.
 *
 * This class intentionally receives the normalized settlement context only. The
 * old controller must not recalculate payment totals before posting to account
 * books. When the legacy account-book call is moved here, it should use:
 * - settlement_no
 * - settlement_id
 * - payment_rows
 * - payment_totals
 * from the context created by PdSettlementPostingContextBuilder.
 */
class PdSettlementPaymentAccountPoster
{
    public function post(array $context): void
    {
        $settlementNo = $context['settlement_no'] ?? null;
        $businessId = $context['business_id'] ?? null;
        $paymentRows = $context['payment_rows'] ?? [];

        if (empty($settlementNo) || empty($businessId)) {
            Log::warning('PD rewrite payment account posting skipped: missing settlement/business', [
                'settlement_no' => $settlementNo,
                'business_id' => $businessId,
            ]);
            return;
        }

        // Guard against duplicate account-book posting while the rewrite is being connected.
        // Existing account rows usually carry operation_date / sub_type / note / ref_no variants.
        // We only inspect safely here; final legacy posting movement will write through this class.
        $alreadyPosted = DB::table('account_transactions')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlementNo) {
                $query->where('note', 'like', '%' . $settlementNo . '%')
                    ->orWhere('ref_no', $settlementNo)
                    ->orWhere('payment_ref_no', $settlementNo);
            })
            ->exists();

        Log::info('PD rewrite payment account posting context ready', [
            'settlement_no' => $settlementNo,
            'already_posted' => $alreadyPosted,
            'payment_rows' => count($paymentRows),
            'payment_details_total' => $context['payment_details_total'] ?? 0,
        ]);
    }
}
