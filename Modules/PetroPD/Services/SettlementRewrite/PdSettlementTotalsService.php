<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;

class PdSettlementTotalsService
{
    public function calculate(array $snapshot): array
    {
        $meterSales = collect($snapshot['meter_sales'] ?? []);
        $payments = collect($snapshot['payments'] ?? []);
        $otherSales = collect($snapshot['other_sales'] ?? []);

        $paymentTotals = $this->paymentTotals($payments);

        return [
            'meter_sales_total' => $this->meterSalesTotal($meterSales),
            'other_sales_total' => $this->otherSalesTotal($otherSales),
            'payment_totals' => $paymentTotals,
            'settlement_total' => $this->paymentDetailsTotal($paymentTotals),
        ];
    }

    public function meterSalesTotal(Collection $rows): float
    {
        return round($rows->sum(fn ($row) => (float) ($row->amount ?? 0)), 6);
    }

    public function otherSalesTotal(Collection $rows): float
    {
        return round($rows->sum(function ($row) {
            $subTotal = (float) ($row->sub_total ?? 0);
            $discountType = $row->discount_type ?? null;
            $discount = (float) ($row->discount ?? 0);
            $discountAmount = (float) ($row->discount_amount ?? 0);

            if ($discountType === 'percentage') {
                return max(0, $subTotal - ($subTotal * $discount / 100));
            }

            if ($discountType === 'fixed') {
                return max(0, $subTotal - ($discountAmount > 0 ? $discountAmount : $discount));
            }

            return $subTotal;
        }), 6);
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
            'owners_drawing' => 0.0,
            'other' => 0.0,
        ];

        foreach ($payments as $payment) {
            $type = $this->normalizePaymentType((string) ($payment->payment_type ?? 'other'));
            $amount = (float) ($payment->payment_amount ?? 0);
            $totals[$type] = ($totals[$type] ?? 0) + $amount;
        }

        return array_map(fn ($amount) => round((float) $amount, 6), $totals);
    }

    public function paymentDetailsTotal(array $paymentTotals): float
    {
        // This is the single total used by List / View / Print / Account posting.
        // Meter sales is intentionally not added here because it is already represented by settlement payment details.
        return round(
            (float) ($paymentTotals['cash'] ?? 0)
            + (float) ($paymentTotals['card'] ?? 0)
            + (float) ($paymentTotals['cheque'] ?? 0)
            + (float) ($paymentTotals['credit_sale'] ?? 0)
            + (float) ($paymentTotals['loan_payment'] ?? 0)
            + (float) ($paymentTotals['loan_to_customer'] ?? 0)
            + (float) ($paymentTotals['excess'] ?? 0)
            - (float) ($paymentTotals['shortage'] ?? 0),
            6
        );
    }

    public function normalizePaymentType(string $type): string
    {
        $type = strtolower(trim(str_replace(['-', ' '], '_', $type)));

        return match ($type) {
            'cards', 'card_payment', 'card' => 'card',
            'cash_payment', 'cash' => 'cash',
            'cheques', 'cheque_payment', 'check', 'cheque' => 'cheque',
            'credit', 'credit_sales', 'credit_sale' => 'credit_sale',
            'expenses', 'expense' => 'expense',
            'short', 'shortage' => 'shortage',
            'excess' => 'excess',
            'loan', 'loan_payment', 'loan_payments' => 'loan_payment',
            'loan_to_customer', 'customer_loan', 'customer_loans' => 'loan_to_customer',
            'drawing', 'owners_drawing', 'owners_drawings' => 'owners_drawing',
            default => 'other',
        };
    }
}
