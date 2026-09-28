<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

class SettlementLoadService
{
    public function __construct(
        protected SettlementSourceRepository $sources,
        protected SettlementTotalsService $totals
    ) {}

    public function loadOldestPendingShift(int $businessId): ?array
    {
        $shift = $this->sources->oldestPendingClosedShift($businessId);

        if (! $shift) {
            return null;
        }

        return $this->loadShift($businessId, (int) $shift->id);
    }

    public function loadShift(int $businessId, int $shiftId): array
    {
        $shift = $this->sources->oldestPendingClosedShift($businessId);
        if (! $shift || (int) $shift->id !== $shiftId) {
            $shift = \Modules\PetroPD\Entities\PetroShift::where('business_id', $businessId)->where('id', $shiftId)->first();
        }

        $assignments = $this->sources->assignmentsForShift($businessId, $shiftId);
        $meterSales = $this->sources->meterSalesForShift($businessId, $shiftId);
        $payments = $this->sources->paymentsForShift($businessId, $shiftId);
        $otherSales = $this->sources->otherSalesForShift($businessId, $shiftId);

        return [
            'shift' => $shift,
            'shift_id' => $shiftId,
            'shift_number' => optional($assignments->first())->shift_number ?? optional($shift)->id,
            'pump_operator_id' => optional($assignments->first())->pump_operator_id ?? optional($shift)->pump_operator_id,
            'assignments' => $assignments,
            'meter_sales' => $meterSales,
            'payments' => $payments,
            'other_sales' => $otherSales,
            'totals' => $this->totals->calculate($meterSales, $payments, $otherSales),
        ];
    }
}
