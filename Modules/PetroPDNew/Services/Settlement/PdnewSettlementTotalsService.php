<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Modules\PetroPDNew\Entities\PdnewSettlement;

class PdnewSettlementTotalsService
{
    private const NON_COLLECTION_PAYMENT_TYPES = ['shortage', 'excess'];

    private const EXPECTED_FIELDS = [
        'meter_sales_total', 'other_sales_total', 'expected_total',
    ];

    private const RECEIVED_FIELDS = [
        'source_payments_total', 'manual_payments_total', 'received_total',
    ];

    public function recalculate(PdnewSettlement $settlement): PdnewSettlement
    {
        $meter = (float) $settlement->meterSales()->sum('amount');
        $other = (float) $settlement->otherSales()->sum('net_amount');

        // Shortage and excess are operational classifications, not collections.
        // This intentionally mirrors Pumper Dashboard-New's shift totals.
        $sourcePayments = (float) $settlement->payments()
            ->where('is_source', true)
            ->where('status', 'active')
            ->whereNotIn('payment_type', self::NON_COLLECTION_PAYMENT_TYPES)
            ->sum('amount');
        $manualPayments = (float) $settlement->payments()
            ->where('is_source', false)
            ->where('status', 'active')
            ->whereNotIn('payment_type', self::NON_COLLECTION_PAYMENT_TYPES)
            ->sum('amount');

        $sourceShortageEntries = (float) $settlement->payments()
            ->where('is_source', true)
            ->where('status', 'active')
            ->where('payment_type', 'shortage')
            ->sum('amount');
        $sourceExcessEntries = (float) $settlement->payments()
            ->where('is_source', true)
            ->where('status', 'active')
            ->where('payment_type', 'excess')
            ->sum('amount');
        $manualShortage = (float) $settlement->payments()
            ->where('is_source', false)
            ->where('status', 'active')
            ->where('payment_type', 'shortage')
            ->sum('amount');
        $manualExcess = (float) $settlement->payments()
            ->where('is_source', false)
            ->where('status', 'active')
            ->where('payment_type', 'excess')
            ->sum('amount');

        $source = $settlement->sourceImport()->first();
        $sourceTotals = (array) ($source?->source_totals ?? []);
        $sourceDeclared = (float) ($sourceTotals['declared_total'] ?? $sourceTotals['payments_total'] ?? $sourcePayments);
        $sourceShortage = (float) ($sourceTotals['shortage_amount'] ?? 0);
        $sourceExcess = (float) ($sourceTotals['excess_amount'] ?? 0);
        if (abs($sourceShortage) < 0.00005 && $sourceShortageEntries > 0) {
            $sourceShortage = $sourceShortageEntries;
        }
        if (abs($sourceExcess) < 0.00005 && $sourceExcessEntries > 0) {
            $sourceExcess = $sourceExcessEntries;
        }

        $recoveryTotal = (float) $settlement->recoveries()
            ->whereNotIn('status', ['void', 'cancelled'])
            ->sum('amount');
        $commissionTotal = (float) $settlement->commissions()
            ->whereNotIn('status', ['void', 'cancelled'])
            ->sum('commission_amount');

        $expectedAdjustments = 0.0;
        $receivedAdjustments = 0.0;

        foreach ($settlement->adjustments()->where('status', 'approved')->orderBy('id')->get() as $adjustment) {
            $delta = $this->delta(
                (string) $adjustment->adjustment_type,
                (float) $adjustment->current_amount,
                (float) $adjustment->approved_amount
            );

            if (in_array((string) $adjustment->field_name, self::EXPECTED_FIELDS, true)) {
                $expectedAdjustments += $delta;
            } elseif (in_array((string) $adjustment->field_name, self::RECEIVED_FIELDS, true)) {
                $receivedAdjustments += $delta;
            }
        }

        $expected = $meter + $other + $expectedAdjustments;
        $received = $sourcePayments + $manualPayments + $receivedAdjustments;
        $operationalVariance = $received - $expected;

        // Classified shortage is added to the accounted side; classified excess
        // is added to the expected/credit side.  A correctly classified shift is
        // therefore balanced without pretending shortage/excess are collections.
        $classifiedShortage = $sourceShortage + $manualShortage;
        $classifiedExcess = $sourceExcess + $manualExcess;
        $unresolvedVariance = $received + $classifiedShortage - $classifiedExcess - $expected;

        $settlement->forceFill([
            'meter_sales_total' => round($meter, 4),
            'other_sales_total' => round($other, 4),
            'source_payments_total' => round($sourcePayments, 4),
            'manual_payments_total' => round($manualPayments, 4),
            'source_declared_total' => round($sourceDeclared, 4),
            'source_shortage_total' => round($sourceShortage, 4),
            'source_excess_total' => round($sourceExcess, 4),
            'manual_shortage_total' => round($manualShortage, 4),
            'manual_excess_total' => round($manualExcess, 4),
            'shortage_recovery_total' => round($recoveryTotal, 4),
            'excess_commission_total' => round($commissionTotal, 4),
            'expected_adjustments_total' => round($expectedAdjustments, 4),
            'received_adjustments_total' => round($receivedAdjustments, 4),
            'adjustments_total' => round($receivedAdjustments - $expectedAdjustments, 4),
            'expected_total' => round($expected, 4),
            'received_total' => round($received, 4),
            'operational_variance_amount' => round($operationalVariance, 4),
            'variance_amount' => round($unresolvedVariance, 4),
            'reconciliation_status' => abs($unresolvedVariance) < 0.00005
                ? 'balanced'
                : ($unresolvedVariance < 0 ? 'shortage' : 'excess'),
        ])->save();

        return $settlement->fresh();
    }

    private function delta(string $type, float $current, float $approved): float
    {
        return match ($type) {
            'increase' => abs($approved),
            'decrease' => -abs($approved),
            default => $approved - $current,
        };
    }
}
