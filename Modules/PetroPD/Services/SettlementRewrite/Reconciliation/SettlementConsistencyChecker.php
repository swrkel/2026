<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Reconciliation;

use Illuminate\Support\Facades\Log;

class SettlementConsistencyChecker
{
    public function __construct(protected SettlementSavedTotalsReader $reader)
    {
    }

    public function check($settlement): array
    {
        $totals = $this->reader->totals($settlement);
        $savedTotal = (float) ($settlement->total_amount ?? $settlement->settlement_total ?? 0);
        $displayTotal = (float) $totals['payment_details_total'];
        $difference = round($savedTotal - $displayTotal, 4);

        $result = [
            'settlement_id' => $totals['settlement_id'],
            'settlement_no' => $totals['settlement_no'],
            'saved_total' => round($savedTotal, 4),
            'payment_details_total' => $displayTotal,
            'difference' => $difference,
            'is_consistent' => abs($difference) < 0.01 || $savedTotal == 0.0,
            'payment_totals' => $totals['payment_totals'],
            'meter_sales_total' => $totals['meter_sales_total'],
            'other_sales_total' => $totals['other_sales_total'],
        ];

        if (! $result['is_consistent']) {
            Log::warning('PD Settlement rewrite consistency mismatch', $result);
        }

        return $result;
    }
}
