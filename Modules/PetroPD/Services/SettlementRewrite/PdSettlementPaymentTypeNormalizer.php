<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

/**
 * Normalizes pumper dashboard / settlement payment type labels into the small
 * set used by the PD Settlement rewrite totals engine.
 */
class PdSettlementPaymentTypeNormalizer
{
    public static function normalize(?string $type): string
    {
        $type = strtolower(trim((string) $type));
        $type = str_replace(['-', '_'], ' ', $type);
        $type = preg_replace('/\s+/', ' ', $type) ?: '';

        return match (true) {
            str_contains($type, 'cash') => 'cash',
            str_contains($type, 'card'), str_contains($type, 'credit debit') => 'card',
            str_contains($type, 'cheque'), str_contains($type, 'check') => 'cheque',
            str_contains($type, 'credit sale'), str_contains($type, 'credit sales'), $type === 'credit' => 'credit_sale',
            str_contains($type, 'loan payment') => 'loan_payment',
            str_contains($type, 'loan to customer'), str_contains($type, 'customer loan') => 'loan_to_customer',
            str_contains($type, 'short') => 'shortage',
            str_contains($type, 'excess') => 'excess',
            str_contains($type, 'expense') => 'expense',
            str_contains($type, 'drawing') => 'owners_drawing',
            default => $type,
        };
    }

    public static function amount($row): float
    {
        return (float) ($row->payment_amount ?? $row->amount ?? $row->total_amount ?? 0);
    }
}
