<?php

namespace Modules\PumperDashboardNew\Services;

use Modules\PumperDashboardNew\Entities\PoneShift;

class PoneShiftTotalsService
{
    public function refresh(PoneShift $shift): PoneShift
    {
        $meter = round((float) $shift->assignments()->where('status', 'closed')->sum('amount'), 4);
        $other = round((float) $shift->otherSales()->where('status', 'confirmed')->sum('net_amount'), 4);
        $normalPayments = round((float) $shift->payments()->where('status', 'confirmed')
            ->whereNotIn('payment_type', ['shortage', 'excess'])->sum('amount'), 4);
        $manualShortage = round((float) $shift->payments()->where('status', 'confirmed')->where('payment_type', 'shortage')->sum('amount'), 4);
        $manualExcess = round((float) $shift->payments()->where('status', 'confirmed')->where('payment_type', 'excess')->sum('amount'), 4);
        $expected = round($meter + $other, 4);

        $latestCollection = $shift->collections()->where('status', 'confirmed')->latest('collection_at')->first();
        $declared = $latestCollection ? round((float) $latestCollection->declared_amount, 4) : $normalPayments;
        $difference = round($expected - $declared, 4);
        $shortage = $manualShortage > 0 ? $manualShortage : max(0, $difference);
        $excess = $manualExcess > 0 ? $manualExcess : max(0, -$difference);
        $status = abs($difference) < 0.00005 ? 'balanced' : ($difference > 0 ? 'shortage' : 'excess');

        $shift->forceFill([
            'meter_sales_total' => $meter,
            'other_sales_total' => $other,
            'payments_total' => $normalPayments,
            'expected_total' => $expected,
            'declared_total' => $declared,
            'shortage_amount' => round($shortage, 4),
            'excess_amount' => round($excess, 4),
            'reconciliation_status' => $latestCollection ? $status : ($shift->reconciliation_status ?: 'pending'),
            'collection_form_no' => $latestCollection?->collection_number ?? $shift->collection_form_no,
        ])->saveQuietly();

        return $shift->fresh();
    }
}
