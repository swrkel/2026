<?php

namespace Modules\Distribution\Services\Payments;

/**
 * Distribution-owned payment helper.
 *
 * This intentionally avoids changing posting logic. It provides a module-local
 * normalization layer for payment rows used by Distribution views, prints and
 * reports.
 */
class DistributionPaymentService
{
    public function normalizeRows(array $payments): array
    {
        $rows = [];

        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'method' => (string) ($payment['method'] ?? $payment['payment_method'] ?? ''),
                'amount' => $amount,
                'reference_no' => (string) ($payment['reference_no'] ?? $payment['cheque_no'] ?? $payment['card_transaction_number'] ?? ''),
                'paid_on' => $payment['paid_on'] ?? $payment['created_at'] ?? null,
            ];
        }

        return $rows;
    }

    public function total(array $payments): float
    {
        return array_reduce($payments, function ($carry, $payment) {
            return $carry + (float) ($payment['amount'] ?? 0);
        }, 0.0);
    }
}
