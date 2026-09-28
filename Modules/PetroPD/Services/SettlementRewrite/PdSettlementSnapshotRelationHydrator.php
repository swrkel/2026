<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;

/**
 * Hydrates legacy Settlement relations from the saved PD settlement snapshot.
 *
 * This class is the bridge that stops List/View/Print from rebuilding figures
 * from different live pumper tables after the settlement is saved.  The legacy
 * Blade files can continue to read the same relation names, but the rows come
 * from one source-of-truth reader.
 */
class PdSettlementSnapshotRelationHydrator
{
    public function __construct(protected PdSettlementSavedSnapshotReader $reader)
    {
    }

    public function hydrate(object $settlement, int $businessId): object
    {
        $settlementNo = (string) ($settlement->settlement_no ?? $settlement->id ?? '');
        $snapshot = $this->reader->snapshot($businessId, (int) ($settlement->id ?? 0), $settlementNo);

        $meterSales = $this->normalizeMeterSales($snapshot['meter_sales'] ?? collect());
        $otherSales = $this->normalizeOtherSales($snapshot['other_sales'] ?? collect());
        $payments = $snapshot['payments'] ?? collect();

        $settlement->setRelation('meter_sales', $meterSales);
        $settlement->setRelation('meter_sales_pd', collect());
        $settlement->setRelation('other_sales', $otherSales);

        $this->setPaymentRelations($settlement, $payments);

        // Store normalized total values on the object for list/view/print/report use.
        $totalsService = app(PdSettlementTotalsService::class);
        $paymentTotals = $totalsService->paymentTotals($payments);
        $settlement->pd_snapshot_payment_totals = $paymentTotals;
        $settlement->pd_snapshot_payment_details_total = $totalsService->paymentDetailsTotal($paymentTotals);
        $settlement->pd_snapshot_meter_total = (float) $meterSales->sum(fn ($row) => (float) ($row->amount ?? $row->discount_amount ?? 0));
        $settlement->pd_snapshot_other_sales_total = (float) $otherSales->sum(fn ($row) => (float) ($row->sub_total ?? $row->amount ?? 0));

        return $settlement;
    }

    protected function setPaymentRelations(object $settlement, Collection $payments): void
    {
        $grouped = $payments->map(function ($row) {
            $row->payment_type = PdSettlementPaymentTypeNormalizer::normalize($row->payment_type ?? $row->type ?? null);
            $amount = PdSettlementPaymentTypeNormalizer::amount($row);
            $row->amount = $amount;
            $row->payment_amount = $amount;
            return $row;
        })->groupBy('payment_type');

        $settlement->setRelation('cash_payments', $this->paymentRows($grouped, ['cash']));
        $settlement->setRelation('card_payments', $this->paymentRows($grouped, ['card']));
        $settlement->setRelation('cheque_payments', $this->paymentRows($grouped, ['cheque']));
        $settlement->setRelation('credit_sale_payments', $this->paymentRows($grouped, ['credit', 'credit_sale', 'credit_sales']));
        $settlement->setRelation('expense_payments', $this->paymentRows($grouped, ['expense', 'expenses']));
        $settlement->setRelation('excess_payments', $this->paymentRows($grouped, ['excess']));
        $settlement->setRelation('shortage_payments', $this->paymentRows($grouped, ['shortage', 'short']));
        $settlement->setRelation('loan_payments', $this->paymentRows($grouped, ['loan_payment', 'loan']));
        $settlement->setRelation('drawings_payments', $this->paymentRows($grouped, ['owners_drawing', 'drawing', 'drawings']));
        $settlement->setRelation('customer_loans', $this->paymentRows($grouped, ['loan_to_customer', 'customer_loan']));
    }

    protected function paymentRows(Collection $grouped, array $types): Collection
    {
        $rows = collect();
        foreach ($types as $type) {
            $rows = $rows->merge($grouped->get($type, collect()));
        }
        return $rows->values();
    }

    protected function normalizeMeterSales(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $amount = (float) ($row->amount ?? $row->discount_amount ?? $row->sub_total ?? 0);
            $row->amount = $amount;
            // Old settlement views often use discount_amount as the meter amount.
            $row->discount_amount = $amount;
            $row->sold_qty = $row->sold_qty ?? $row->sold_ltr ?? 0;
            $row->sold_ltr = $row->sold_ltr ?? $row->sold_qty ?? 0;
            return $row;
        })->values();
    }

    protected function normalizeOtherSales(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $row->sub_total = (float) ($row->sub_total ?? $row->amount ?? 0);
            $row->discount_amount = (float) ($row->discount_amount ?? 0);
            return $row;
        })->values();
    }
}
