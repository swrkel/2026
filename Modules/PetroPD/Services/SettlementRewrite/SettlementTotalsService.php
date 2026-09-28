<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;

class SettlementTotalsService
{
    public function calculate(Collection $meterSales, Collection $payments, Collection $otherSales): array
    {
        $paymentTotals = $this->paymentTotals($payments);
        $meterTotal = $this->sumColumn($meterSales, 'amount');
        $otherSaleTotal = $this->sumColumn($otherSales, 'sub_total');

        $finalTotal =
            ($paymentTotals['cash'] ?? 0)
            + ($paymentTotals['card'] ?? 0)
            + ($paymentTotals['cheque'] ?? 0)
            + ($paymentTotals['credit_sale'] ?? 0)
            + ($paymentTotals['loan_payment'] ?? 0)
            + ($paymentTotals['loan_to_customer'] ?? 0)
            + ($paymentTotals['excess'] ?? 0)
            - ($paymentTotals['shortage'] ?? 0);

        return [
            'meter_sale_total' => round($meterTotal, 6),
            'other_sale_total' => round($otherSaleTotal, 6),
            'payment_totals' => $paymentTotals,
            'settlement_total' => round($finalTotal, 6),
        ];
    }

    public function paymentTotals(Collection $payments): array
    {
        $totals = [
            'cash' => 0.0,
            'card' => 0.0,
            'cheque' => 0.0,
            'credit_sale' => 0.0,
            'expense' => 0.0,
            'shortage' => 0.0,
            'excess' => 0.0,
            'loan_payment' => 0.0,
            'loan_to_customer' => 0.0,
            'other' => 0.0,
        ];

        foreach ($payments as $payment) {
            $type = $this->normalizePaymentType((string) $payment->payment_type);
            $amount = (float) str_replace(',', '', (string) $payment->payment_amount);
            $totals[$type] = ($totals[$type] ?? 0) + $amount;
        }

        return array_map(fn ($value) => round((float) $value, 6), $totals);
    }

    private function normalizePaymentType(string $type): string
    {
        $key = strtolower(trim(str_replace(['-', ' '], '_', $type)));

        return match ($key) {
            'cash' => 'cash',
            'card', 'cards', 'credit_card', 'visa', 'master' => 'card',
            'cheque', 'check' => 'cheque',
            'credit', 'credit_sale', 'credit_sales' => 'credit_sale',
            'expense', 'expenses' => 'expense',
            'short', 'shortage' => 'shortage',
            'excess' => 'excess',
            'loan', 'loan_payment', 'loan_payments' => 'loan_payment',
            'loan_to_customer', 'customer_loan' => 'loan_to_customer',
            default => 'other',
        };
    }

    private function sumColumn(Collection $rows, string $column): float
    {
        return (float) $rows->sum(function ($row) use ($column) {
            return (float) str_replace(',', '', (string) ($row->{$column} ?? 0));
        });
    }
}
