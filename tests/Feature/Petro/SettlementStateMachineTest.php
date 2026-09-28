<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementStateMachine;
use Modules\Petro\Services\ShiftState;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;

/**
 * @group characterization
 */
class SettlementStateMachineTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function maps_raw_flag_combinations_to_canonical_shift_states(): void
    {
        $machine = app(SettlementStateMachine::class);

        $draft = $this->seedAssignmentWithShift(['status' => 'open'], ['status' => 1, 'closed_time' => null]);
        $closed = $this->seedAssignmentWithShift(['status' => 'close', 'close_date_and_time' => now(), 'is_manually_closed' => 1], ['status' => 2, 'closed_time' => now()]);
        $inSettlement = $this->seedAssignmentWithShift(['status' => 'close', 'settlement_id' => 990001, 'closed_in_settlement' => 0], ['status' => 2, 'closed_time' => now()]);
        $settled = $this->seedAssignmentWithShift(['status' => 'close', 'settlement_id' => 990002, 'closed_in_settlement' => 1], ['status' => 2, 'closed_time' => now()]);
        $reopened = $this->seedAssignmentWithShift(['status' => 'open', 'is_manually_closed' => 1, 'closed_in_settlement' => 0, 'settlement_id' => null], ['status' => 0, 'closed_time' => null]);

        $this->assertSame(ShiftState::Draft, $machine->shiftState($draft));
        $this->assertSame(ShiftState::PumperClosed, $machine->shiftState($closed));
        $this->assertSame(ShiftState::InSettlement, $machine->shiftState($inSettlement));
        $this->assertSame(ShiftState::Settled, $machine->shiftState($settled));
        $this->assertSame(ShiftState::Reopened, $machine->shiftState($reopened));
    }

    /** @test */
    public function transitions_set_assignment_and_shift_flags_atomically(): void
    {
        $shiftId = $this->seedAssignmentWithShift(['status' => 'open'], ['status' => 1, 'closed_time' => null]);
        $machine = app(SettlementStateMachine::class);

        $machine->transition($shiftId, ShiftState::PumperClosed);
        $this->assertSame(ShiftState::PumperClosed, $machine->shiftState($shiftId));
        $this->assertAssignmentFlags($shiftId, 'close', 1, 0, null, 2);

        $machine->transition($shiftId, ShiftState::InSettlement);
        $this->assertSame(ShiftState::InSettlement, $machine->shiftState($shiftId));
        $this->assertAssignmentFlags($shiftId, 'close', 1, 0, 0, 2);

        $machine->transition($shiftId, ShiftState::Settled);
        $this->assertSame(ShiftState::Settled, $machine->shiftState($shiftId));
        $this->assertAssignmentFlags($shiftId, 'close', 1, 1, 0, 2);

        $machine->transition($shiftId, ShiftState::Reopened);
        $this->assertSame(ShiftState::Reopened, $machine->shiftState($shiftId));
        $this->assertAssignmentFlags($shiftId, 'open', 1, 0, null, 0);
    }

    /** @test */
    public function pending_closed_query_uses_state_machine_state_filter(): void
    {
        $pending = $this->seedAssignmentWithShift(['status' => 'close', 'close_date_and_time' => now(), 'is_manually_closed' => 1], ['status' => 2, 'closed_time' => now()]);
        $settled = $this->seedAssignmentWithShift(['status' => 'close', 'close_date_and_time' => now(), 'is_manually_closed' => 1, 'closed_in_settlement' => 1, 'settlement_id' => 990003], ['status' => 2, 'closed_time' => now()]);
        $draft = $this->seedAssignmentWithShift(['status' => 'open'], ['status' => 1, 'closed_time' => null]);

        $ids = PetroPdClosedShiftQuery::pendingClosedBase($this->businessId)
            ->pluck('pump_operator_assignments.shift_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->assertContains($pending, $ids);
        $this->assertNotContains($settled, $ids);
        $this->assertNotContains($draft, $ids);
    }

    private function seedAssignmentWithShift(array $assignmentOverrides = [], array $shiftOverrides = []): int
    {
        $pumpId = (int) DB::table('pumps')->where('business_id', $this->businessId)->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = DB::table('petro_shifts')->insertGetId(array_merge([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1,
            'shift_date' => now(),
            'closed_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $shiftOverrides));

        DB::table('pump_operator_assignments')->insert(array_merge([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 0,
            'date_and_time' => now(),
            'close_date_and_time' => null,
            'status' => 'open',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 0,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => $shiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $assignmentOverrides));

        return $shiftId;
    }

    private function assertAssignmentFlags(int $shiftId, string $assignmentStatus, int $manualClosed, int $closedInSettlement, ?int $settlementId, int $shiftStatus): void
    {
        $assignment = DB::table('pump_operator_assignments')->where('shift_id', $shiftId)->first();
        $shift = DB::table('petro_shifts')->where('id', $shiftId)->first();

        $this->assertSame($assignmentStatus, $assignment->status);
        $this->assertSame($manualClosed, (int) $assignment->is_manually_closed);
        $this->assertSame($closedInSettlement, (int) $assignment->closed_in_settlement);
        $this->assertSame($settlementId, $assignment->settlement_id !== null ? (int) $assignment->settlement_id : null);
        $this->assertSame($shiftStatus, (int) $shift->status);
    }
}
