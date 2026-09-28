<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;

class PdSettlementTotalsCalculator
{
    public function calculate(Collection $meterSales, Collection $payments, Collection $otherSales): array
    {
        $paymentTotals = $payments->groupBy(function ($row) {
            return $this->normalizeType((string) $row->payment_type);
        })->map(function ($rows) {
            return round((float) $rows->sum(function ($row) {
                return (float) str_replace(',', '', (string) $row->payment_amount);
            }), 4);
        });

        $meterTotal = round((float) $meterSales->sum('amount'), 4);
        $otherSaleTotal = round((float) $otherSales->sum('sub_total'), 4);

        $cash = (float) $paymentTotals->get('cash', 0);
        $card = (float) $paymentTotals->get('card', 0);
        $cheque = (float) $paymentTotals->get('cheque', 0);
        $creditSales = (float) $paymentTotals->get('credit_sales', 0);
        $loanPayments = (float) $paymentTotals->get('loan_payments', 0);
        $loanToCustomer = (float) $paymentTotals->get('loan_to_customer', 0);
        $expense = (float) $paymentTotals->get('expense', 0);
        $shortage = (float) $paymentTotals->get('shortage', 0);
        $excess = (float) $paymentTotals->get('excess', 0);

        // This total is the Payment Details Total used by List/View/Print/Reports.
        // Meter sales and other sales are displayed separately and are NOT added here.
        $paymentDetailsTotal = round(
            $cash + $card + $cheque + $creditSales + $loanPayments + $loanToCustomer + $excess - $shortage - $expense,
            4
        );

        return [
            'meter_sale_total' => $meterTotal,
            'other_sale_total' => $otherSaleTotal,
            'cash' => $cash,
            'card' => $card,
            'cheque' => $cheque,
            'credit_sales' => $creditSales,
            'loan_payments' => $loanPayments,
            'loan_to_customer' => $loanToCustomer,
            'expense' => $expense,
            'shortage' => $shortage,
            'excess' => $excess,
            'payment_details_total' => $paymentDetailsTotal,
            'raw_payment_totals' => $paymentTotals->all(),
        ];
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = str_replace(['-', ' '], '_', $type);

        return match ($type) {
            'cards', 'card_payment' => 'card',
            'cheques', 'cheque_payment' => 'cheque',
            'credit', 'credit_sale', 'credit_sales_payment' => 'credit_sales',
            'loan', 'loan_payment' => 'loan_payments',
            'customer_loan', 'customer_loans', 'loan_to_customer' => 'loan_to_customer',
            'expenses', 'expense_payment' => 'expense',
            'short', 'short_payment', 'shortage_payment' => 'shortage',
            'excess_payment' => 'excess',
            default => $type,
        };
    }
}
