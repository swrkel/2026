<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Customer ledger and pump-operator ledger posting seam.
 */
class PdSettlementLedgerPoster
{
    public function post(array $context): void
    {
        $settlementNo = $context['settlement_no'] ?? null;
        $businessId = $context['business_id'] ?? null;

        if (empty($settlementNo) || empty($businessId)) {
            Log::warning('PD rewrite ledger posting skipped: missing settlement/business', [
                'settlement_no' => $settlementNo,
                'business_id' => $businessId,
            ]);
            return;
        }

        $customerLedgerExists = DB::table('contact_ledger')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlementNo) {
                $query->where('description', 'like', '%' . $settlementNo . '%')
                    ->orWhere('ref_no', $settlementNo);
            })
            ->exists();

        Log::info('PD rewrite ledger posting context ready', [
            'settlement_no' => $settlementNo,
            'customer_ledger_exists' => $customerLedgerExists,
            'pump_operator_id' => $context['pump_operator_id'] ?? null,
            'credit_sales_total' => $context['payment_totals']['credit_sale'] ?? 0,
            'shortage_total' => $context['payment_totals']['shortage'] ?? 0,
            'excess_total' => $context['payment_totals']['excess'] ?? 0,
        ]);
    }
}
