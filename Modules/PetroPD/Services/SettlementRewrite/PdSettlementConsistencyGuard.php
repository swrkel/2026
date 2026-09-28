<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Facades\Log;

/**
 * Validates that every saved settlement page can display one consistent amount.
 */
class PdSettlementConsistencyGuard
{
    public function assertConsistent(array $postingContext): void
    {
        $paymentTotal = round((float) ($postingContext['payment_details_total'] ?? 0), 6);
        $partsTotal = round(
            (float) ($postingContext['cash_total'] ?? 0)
            + (float) ($postingContext['card_total'] ?? 0)
            + (float) ($postingContext['cheque_total'] ?? 0)
            + (float) ($postingContext['credit_sale_total'] ?? 0)
            + (float) ($postingContext['loan_payment_total'] ?? 0)
            + (float) ($postingContext['loan_to_customer_total'] ?? 0)
            + (float) ($postingContext['excess_total'] ?? 0)
            - (float) ($postingContext['shortage_total'] ?? 0),
            6
        );

        if (abs($paymentTotal - $partsTotal) > 0.000001) {
            Log::warning('PD Settlement rewrite consistency warning: payment total mismatch', [
                'settlement_id' => $postingContext['settlement_id'] ?? null,
                'settlement_no' => $postingContext['settlement_no'] ?? null,
                'payment_details_total' => $paymentTotal,
                'recalculated_parts_total' => $partsTotal,
            ]);
        }
    }
}
