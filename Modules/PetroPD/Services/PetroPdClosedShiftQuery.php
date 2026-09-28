<?php

namespace Modules\PetroPD\Services;

use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Services\SettlementStateMachine;
use Modules\PetroPD\Services\ShiftState;

/**
 * Shifts closed in Pumper Dashboard but not yet finalized in Petro PD settlement.
 */
class PetroPdClosedShiftQuery
{
    /**
     * Base query: assignment is closed in dashboard, pending in PD settlement.
     */
    public static function pendingClosedBase(int $businessId, ?int $pumpOperatorId = null)
    {
        $q = PumpOperatorAssignment::query()
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.business_id', $businessId)
            ->where('ps.status', 2);

        app(SettlementStateMachine::class)->scopeAssignmentsInState($q, ShiftState::PumperClosed);

        if ($pumpOperatorId !== null) {
            $q->where('pump_operator_assignments.pump_operator_id', $pumpOperatorId);
        }

        return $q;
    }

    /**
     * Oldest pending closed shift for one operator (minimum numeric shift_number).
     */
    public static function oldestPendingForOperator(int $businessId, int $pumpOperatorId): ?object
    {
        return static::pendingClosedBase($businessId, $pumpOperatorId)
            ->select(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->groupBy(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->orderByRaw('CAST(pump_operator_assignments.shift_number AS UNSIGNED) ASC')
            ->orderBy('pump_operator_assignments.shift_id')
            ->first();
    }

    /**
     * Globally oldest pending shift (across operators) by numeric shift number.
     */
    public static function oldestPendingGlobally(int $businessId): ?object
    {
        return static::pendingClosedBase($businessId)
            ->select(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->groupBy(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->orderByRaw('CAST(pump_operator_assignments.shift_number AS UNSIGNED) ASC')
            ->orderBy('pump_operator_assignments.shift_id')
            ->first();
    }

    /**
     * True if this shift_id is the next one that must be settled for its pump operator.
     */
    public static function isNextAllowedShift(int $businessId, int $shiftId, ?int $pumpOperatorId = null): bool
    {
        $q = PumpOperatorAssignment::where('business_id', $businessId)
            ->where('shift_id', $shiftId);

        if ($pumpOperatorId !== null) {
            $q->where('pump_operator_id', $pumpOperatorId);
        }

        $row = $q->orderBy('id')->first();

        if (! $row) {
            return false;
        }

        $oldest = static::oldestPendingForOperator($businessId, (int) $row->pump_operator_id);

        if (! $oldest) {
            return false;
        }

        return (int) $oldest->shift_id === (int) $shiftId;
    }
}
