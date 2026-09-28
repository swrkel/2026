<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;

class PaymentAccountBookPostingService
{
    public function __construct(protected SettlementPostingTableGuard $guard) {}

    public function post(PdSettlementPostingPayload $payload, SettlementPostingResult $result): void
    {
        // This service intentionally posts from the saved settlement/payment snapshot payload only.
        // It must never recalculate totals from live pumper tables after settlement save.
        $paymentTotals = $payload->totals['payment_totals'] ?? [];

        if (! $this->guard->hasColumns('transactions', ['business_id', 'type', 'ref_no'])) {
            $result->skipped('payment_account_books', 'transactions table or expected columns are not available in this install.');
            return;
        }

        $existing = DB::table('transactions')
            ->where('business_id', $payload->businessId)
            ->where('type', 'petro_pd_settlement')
            ->where('ref_no', $payload->settlementNo)
            ->exists();

        if ($existing) {
            $result->skipped('payment_account_books', 'Already posted for this settlement number.');
            return;
        }

        // The old ERP accounting schema differs between installations. The final posting
        // mapping is centralized here so List/View/Print totals remain independent from it.
        $result->posted('payment_account_books', [
            'cash' => (float) ($paymentTotals['cash'] ?? 0),
            'card' => (float) ($paymentTotals['card'] ?? 0),
            'cheque' => (float) ($paymentTotals['cheque'] ?? 0),
            'credit_sale' => (float) ($paymentTotals['credit_sale'] ?? 0),
            'loan_payment' => (float) ($paymentTotals['loan_payment'] ?? 0),
            'loan_to_customer' => (float) ($paymentTotals['loan_to_customer'] ?? 0),
            'shortage' => (float) ($paymentTotals['shortage'] ?? 0),
            'excess' => (float) ($paymentTotals['excess'] ?? 0),
            'settlement_total' => (float) ($payload->totals['settlement_total'] ?? 0),
        ]);
    }
}
