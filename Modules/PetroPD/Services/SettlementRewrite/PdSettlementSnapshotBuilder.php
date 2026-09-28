<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use RuntimeException;

class PdSettlementSnapshotBuilder
{
    public function __construct(private PdSettlementSourceRepository $sources, private PdSettlementTotalsCalculator $totals)
    {
    }

    public function buildOldestPending(int $businessId): array
    {
        $shift = $this->sources->oldestPendingClosedShift($businessId);

        if (! $shift) {
            throw new RuntimeException('No pending closed PD shift found for settlement.');
        }

        return $this->buildForShift($businessId, (int) $shift->id);
    }

    public function buildForShift(int $businessId, int $shiftId): array
    {
        $shift = $this->sources->oldestPendingClosedShift($businessId);
        if ($shift && (int) $shift->id !== $shiftId) {
            // Settlement must always process oldest pending shift first.
            $shiftId = (int) $shift->id;
        }

        $assignments = $this->sources->closedAssignments($businessId, $shiftId);
        $meterSales = $this->sources->meterSales($businessId, $assignments);
        $payments = $this->sources->payments($businessId, $shiftId);
        $otherSales = $this->sources->otherSales($businessId, $shiftId);

        return [
            'shift' => $shift,
            'assignments' => $assignments,
            'meter_sales' => $meterSales,
            'payments' => $payments,
            'other_sales' => $otherSales,
            'totals' => $this->totals->calculate($meterSales, $payments, $otherSales),
        ];
    }
}
