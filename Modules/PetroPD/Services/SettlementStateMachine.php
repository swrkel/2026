<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Entities\PumpOperatorAssignment;

class SettlementStateMachine
{
    public function shiftState(int $shiftId): ShiftState
    {
        $row = PumpOperatorAssignment::query()
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.shift_id', $shiftId)
            ->select(
                'pump_operator_assignments.status',
                'pump_operator_assignments.is_manually_closed',
                'pump_operator_assignments.closed_in_settlement',
                'pump_operator_assignments.settlement_id',
                'pump_operator_assignments.close_date_and_time',
                'ps.status as shift_status',
                'ps.closed_time'
            )
            ->orderBy('pump_operator_assignments.id')
            ->first();

        if (! $row) {
            throw new \InvalidArgumentException("No pump operator assignment found for shift_id {$shiftId}");
        }

        if (! empty($row->closed_in_settlement) && $row->settlement_id !== null) {
            return ShiftState::Settled;
        }

        if ($row->settlement_id !== null) {
            return ShiftState::InSettlement;
        }

        if ($row->status === 'open' && ! empty($row->is_manually_closed)) {
            return ShiftState::Reopened;
        }

        if ($this->isClosed($row)) {
            return ShiftState::PumperClosed;
        }

        return ShiftState::Draft;
    }

    public function transition(int $shiftId, ShiftState $to): void
    {
        DB::transaction(function () use ($shiftId, $to) {
            $assignment = PumpOperatorAssignment::where('shift_id', $shiftId)->orderBy('id')->firstOrFail();
            $now = now();

            $assignmentUpdate = match ($to) {
                ShiftState::Draft => [
                    'status' => 'open',
                    'is_manually_closed' => 0,
                    'closed_in_settlement' => 0,
                    'settlement_id' => null,
                    'close_date_and_time' => null,
                ],
                ShiftState::PumperClosed => [
                    'status' => 'close',
                    'is_manually_closed' => 1,
                    'closed_in_settlement' => 0,
                    'settlement_id' => null,
                    'close_date_and_time' => $assignment->close_date_and_time ?: $now,
                ],
                ShiftState::InSettlement => [
                    'status' => 'close',
                    'is_manually_closed' => 1,
                    'closed_in_settlement' => 0,
                    'settlement_id' => $assignment->settlement_id ?: 0,
                    'close_date_and_time' => $assignment->close_date_and_time ?: $now,
                ],
                ShiftState::Settled => [
                    'status' => 'close',
                    'is_manually_closed' => 1,
                    'closed_in_settlement' => 1,
                    'settlement_id' => $assignment->settlement_id ?: 0,
                    'close_date_and_time' => $assignment->close_date_and_time ?: $now,
                ],
                ShiftState::Reopened => [
                    'status' => 'open',
                    'is_manually_closed' => 1,
                    'closed_in_settlement' => 0,
                    'settlement_id' => null,
                    'close_date_and_time' => null,
                ],
            };

            $shiftUpdate = match ($to) {
                ShiftState::Draft => ['status' => 1, 'closed_time' => null],
                ShiftState::PumperClosed, ShiftState::InSettlement, ShiftState::Settled => ['status' => 2, 'closed_time' => $now],
                ShiftState::Reopened => ['status' => 0, 'closed_time' => null],
            };

            PumpOperatorAssignment::where('shift_id', $shiftId)->update($assignmentUpdate);
            DB::table('petro_shifts')->where('id', $shiftId)->update(array_merge($shiftUpdate, [
                'updated_at' => $now,
            ]));
        });
    }

    public function scopeAssignmentsInState(Builder $query, ShiftState $state): Builder
    {
        return match ($state) {
            ShiftState::Draft => $query
                ->where('pump_operator_assignments.status', 'open')
                ->where(function ($q) {
                    $q->where('pump_operator_assignments.is_manually_closed', 0)
                        ->orWhereNull('pump_operator_assignments.is_manually_closed');
                })
                ->whereNull('pump_operator_assignments.settlement_id'),
            ShiftState::PumperClosed => $query
                ->where('pump_operator_assignments.status', 'close')
                ->whereNotNull('pump_operator_assignments.close_date_and_time')
                ->where(function ($q) {
                    $q->where('pump_operator_assignments.is_manually_closed', 1)
                        ->orWhere('ps.status', 2)
                        ->orWhereNotNull('ps.closed_time');
                })
                // Pumper Dashboard sets closed_in_settlement=1 after safely posting the
                // close-shift meter-sale batch. The shift is still pending for Petro PD until
                // a settlement_id is linked, so settlement_id is the authoritative boundary.
                ->whereNull('pump_operator_assignments.settlement_id'),
            ShiftState::InSettlement => $query
                ->whereNotNull('pump_operator_assignments.settlement_id')
                ->where(function ($q) {
                    $q->where('pump_operator_assignments.closed_in_settlement', 0)
                        ->orWhereNull('pump_operator_assignments.closed_in_settlement');
                }),
            ShiftState::Settled => $query
                ->whereNotNull('pump_operator_assignments.settlement_id')
                ->where('pump_operator_assignments.closed_in_settlement', 1),
            ShiftState::Reopened => $query
                ->where('pump_operator_assignments.status', 'open')
                ->where('pump_operator_assignments.is_manually_closed', 1)
                ->whereNull('pump_operator_assignments.settlement_id'),
        };
    }

    private function isClosed(object $row): bool
    {
        return $row->status === 'close'
            && (
                ! empty($row->is_manually_closed)
                || (int) $row->shift_status === 2
                || ! empty($row->closed_time)
            );
    }
}
