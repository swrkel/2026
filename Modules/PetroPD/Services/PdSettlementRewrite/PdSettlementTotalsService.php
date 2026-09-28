<?php

namespace Modules\PetroPD\Services\PdSettlementRewrite;

use Illuminate\Support\Collection;

class PdSettlementTotalsService
{
    public const PAYMENT_TABLES = [
        'cash' => 'settlement_cash_payments',
        'cards' => 'settlement_card_payments',
        'cheques' => 'settlement_cheque_payments',
        'credit_sales' => 'settlement_credit_sale_payments',
        'customer_loans' => 'settlement_customer_loans',
        'loan_payments' => 'settlement_loan_payments',
        'drawings' => 'settlement_drawing_payments',
        'expenses' => 'settlement_expense_payments',
        'excess' => 'settlement_excess_payments',
        'shortage' => 'settlement_shortage_payments',
        'cash_deposits' => 'settlement_cash_deposits',
    ];

    public function rowAmount(object $row): float
    {
        foreach (['amount', 'total_amount', 'sub_total', 'paid_amount', 'payment_amount'] as $column) {
            if (isset($row->{$column})) {
                return (float) $row->{$column};
            }
        }

        return 0.0;
    }

    public function collectionTotal(Collection $rows): float
    {
        return round($rows->sum(fn ($row) => $this->rowAmount($row)), 4);
    }

    public function paymentDetailsTotal(array $paymentCollections): float
    {
        $add = ['cash', 'cards', 'cheques', 'credit_sales', 'customer_loans', 'loan_payments', 'drawings', 'excess'];
        $subtract = ['shortage'];

        $total = 0.0;
        foreach ($add as $key) {
            $total += isset($paymentCollections[$key]) ? $this->collectionTotal($paymentCollections[$key]) : 0.0;
        }
        foreach ($subtract as $key) {
            $total -= isset($paymentCollections[$key]) ? $this->collectionTotal($paymentCollections[$key]) : 0.0;
        }

        return round($total, 4);
    }
}
