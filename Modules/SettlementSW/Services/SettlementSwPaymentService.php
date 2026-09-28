<?php

namespace Modules\SettlementSW\Services;

/**
 * SW_SEP_003
 * Central payment helper service for cash/card/cheque/loan/drawing/expense payments.
 */
class SettlementSwPaymentService extends SettlementSwBaseService
{
    public function totalsByMethod($payments): array
    {
        $payments = collect($payments ?: []);

        return [
            'cash' => $this->sumAmount($payments->where('payment_method', 'cash')),
            'card' => $this->sumAmount($payments->where('payment_method', 'card')),
            'cheque' => $this->sumAmount($payments->where('payment_method', 'cheque')),
            'loan' => $this->sumAmount($payments->where('payment_method', 'loan')),
            'drawing' => $this->sumAmount($payments->where('payment_method', 'drawing')),
            'expense' => $this->sumAmount($payments->where('payment_method', 'expense')),
            'total' => $this->sumAmount($payments),
        ];
    }

    public function netBalance(float $salesTotal, float $paymentTotal, float $expenseTotal = 0): float
    {
        return $this->money($salesTotal - $paymentTotal - $expenseTotal);
    }
}
