<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;

/**
 * Builds one posting context from the saved/loaded PD settlement snapshot.
 *
 * Account books, customer ledgers, pump-operator ledgers and stock posting must
 * use this context so all financial pages use the same totals as List/View/Print.
 */
class PdSettlementPostingContextBuilder
{
    public function __construct(protected PdSettlementTotalsService $totalsService)
    {
    }

    public function build(array $context): array
    {
        $snapshot = $context['snapshot'] ?? [];
        $totals = $context['totals'] ?? $this->totalsService->calculate($snapshot);
        $paymentTotals = $totals['payment_totals'] ?? [];

        return [
            'business_id' => (int) ($context['business_id'] ?? 0),
            'settlement_id' => (int) ($context['settlement_id'] ?? 0),
            'settlement_no' => (string) ($context['settlement_no'] ?? ''),
            'shift_id' => ! empty($context['shift_id']) ? (int) $context['shift_id'] : null,
            'pump_operator_id' => ! empty($context['pump_operator_id']) ? (int) $context['pump_operator_id'] : null,
            'meter_sales_total' => (float) ($totals['meter_sales_total'] ?? 0),
            'other_sales_total' => (float) ($totals['other_sales_total'] ?? 0),
            'payment_details_total' => (float) ($totals['settlement_total'] ?? 0),
            'cash_total' => (float) ($paymentTotals['cash'] ?? 0),
            'card_total' => (float) ($paymentTotals['card'] ?? 0),
            'cheque_total' => (float) ($paymentTotals['cheque'] ?? 0),
            'credit_sale_total' => (float) ($paymentTotals['credit_sale'] ?? 0),
            'expense_total' => (float) ($paymentTotals['expense'] ?? 0),
            'shortage_total' => (float) ($paymentTotals['shortage'] ?? 0),
            'excess_total' => (float) ($paymentTotals['excess'] ?? 0),
            'loan_payment_total' => (float) ($paymentTotals['loan_payment'] ?? 0),
            'loan_to_customer_total' => (float) ($paymentTotals['loan_to_customer'] ?? 0),
            'owners_drawing_total' => (float) ($paymentTotals['owners_drawing'] ?? 0),
            'meter_sales' => $this->normaliseRows($snapshot['meter_sales'] ?? collect()),
            'payments' => $this->normaliseRows($snapshot['payments'] ?? collect()),
            'other_sales' => $this->normaliseRows($snapshot['other_sales'] ?? collect()),
        ];
    }

    protected function normaliseRows($rows): Collection
    {
        return $rows instanceof Collection ? $rows->values() : collect($rows)->values();
    }
}
