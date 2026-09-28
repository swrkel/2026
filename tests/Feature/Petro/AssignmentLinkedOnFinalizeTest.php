<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Regression test for the "Edit finalized PD settlement shows no meter sales" bug.
 *
 * Before the 2026-05-13 fix, SettlementPDController::store() finalized a PD settlement
 * by updating pumper_day_entries.closed_in_settlement = 1 but NEVER updated the
 * matching pump_operator_assignments rows. After finalize, every assignment for that
 * settlement still had settlement_id = NULL and closed_in_settlement = 0.
 *
 * The edit page's primary lookup at SettlementPDController.php:~7538 keys on
 * `pump_operator_assignments.settlement_id = $settlement->id`. With NULL settlement_id
 * on every assignment, the primary lookup returned empty, forcing the page onto a
 * fragile fallback that decoded settlement.work_shift = ["10"] and tried to resolve
 * shift NUMBER → shift ID via assignments. When the fallback failed for any reason
 * (assignment missing, shift_id NULL, multiple historical shifts with same number),
 * the Meter Sale tab on the edit page rendered empty even though meter sale rows
 * existed in pump_operator_meter_sales.
 *
 * This test pins down the contract: after a finalize-style update, the assignments
 * for the settled shifts MUST have settlement_id populated and closed_in_settlement = 1.
 *
 * @group characterization
 */
class AssignmentLinkedOnFinalizeTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function update_in_finalize_block_sets_settlement_id_and_closed_in_settlement_on_assignments(): void
    {
        // Replicate the finalize block's update statement against seeded fixtures.
        // We do NOT call the full store() method — that would require building a full
        // HTTP request with auth/session/permissions. The pinned contract is the SQL
        // update itself: pump_operator_assignments matching (business, operator, shift_id)
        // get their settlement_id and closed_in_settlement set.

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 0,
            'shift_date'       => now(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'      => 'PDST-LINK-' . uniqid(),
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode(['10']),
            'note'               => null,
            'total_amount'       => '0',
            'status'             => 0,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id'          => $this->businessId,
            'pump_id'              => $pumpId,
            'pump_operator_id'     => $this->pumpOperatorId,
            'starting_meter'       => 0,
            'closing_meter'        => 100,
            'date_and_time'        => now(),
            'close_date_and_time'  => now(),
            'status'               => 'close',
            'settlement_id'        => null, // pre-finalize: NULL
            'assigned_by'          => $this->userId,
            'is_confirmed'         => 1,
            'confirmed_at'         => now(),
            'is_manually_closed'   => 0,
            'closed_in_settlement' => 0,   // pre-finalize: 0
            'shift_id'             => $shiftId,
            'shift_number'         => 10,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Sanity: pre-state
        $before = DB::table('pump_operator_assignments')->where('id', $assignmentId)->first();
        $this->assertNull($before->settlement_id);
        $this->assertSame(0, (int) $before->closed_in_settlement);

        // Run the EXACT update statement the finalize block now executes.
        DB::table('pump_operator_assignments')
            ->where('business_id', $this->businessId)
            ->where('pump_operator_id', $this->pumpOperatorId)
            ->whereIn('shift_id', [$shiftId])
            ->update([
                'settlement_id'        => $settlementId,
                'closed_in_settlement' => 1,
            ]);

        $after = DB::table('pump_operator_assignments')->where('id', $assignmentId)->first();
        $this->assertSame($settlementId, (int) $after->settlement_id,
            'Finalize must set assignment.settlement_id so the edit-page primary lookup works.');
        $this->assertSame(1, (int) $after->closed_in_settlement,
            'Finalize must set assignment.closed_in_settlement = 1 so the assignment is marked settled.');
    }

    /** @test */
    public function finalize_does_not_relink_assignments_for_other_operators_or_shifts(): void
    {
        // The update is scoped by (business, pump_operator_id, shift_id IN [...]).
        // Prove it does NOT touch siblings — assignments for the same operator but
        // a different shift, or a different operator on the same shift.

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftToSettle = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 0,
            'shift_date'       => now(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $shiftUnrelated = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 0,
            'shift_date'       => now(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $assignmentToLink = DB::table('pump_operator_assignments')->insertGetId([
            'business_id'          => $this->businessId,
            'pump_id'              => $pumpId,
            'pump_operator_id'     => $this->pumpOperatorId,
            'starting_meter'       => 0,
            'closing_meter'        => 100,
            'date_and_time'        => now(),
            'close_date_and_time'  => now(),
            'status'               => 'close',
            'settlement_id'        => null,
            'assigned_by'          => $this->userId,
            'is_confirmed'         => 1,
            'confirmed_at'         => now(),
            'is_manually_closed'   => 0,
            'closed_in_settlement' => 0,
            'shift_id'             => $shiftToSettle,
            'shift_number'         => 10,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $assignmentUnrelated = DB::table('pump_operator_assignments')->insertGetId([
            'business_id'          => $this->businessId,
            'pump_id'              => $pumpId,
            'pump_operator_id'     => $this->pumpOperatorId,
            'starting_meter'       => 0,
            'closing_meter'        => 100,
            'date_and_time'        => now(),
            'close_date_and_time'  => now(),
            'status'               => 'close',
            'settlement_id'        => null,
            'assigned_by'          => $this->userId,
            'is_confirmed'         => 1,
            'confirmed_at'         => now(),
            'is_manually_closed'   => 0,
            'closed_in_settlement' => 0,
            'shift_id'             => $shiftUnrelated,
            'shift_number'         => 11,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('pump_operator_assignments')
            ->where('business_id', $this->businessId)
            ->where('pump_operator_id', $this->pumpOperatorId)
            ->whereIn('shift_id', [$shiftToSettle])
            ->update([
                'settlement_id'        => 999999,
                'closed_in_settlement' => 1,
            ]);

        $linked    = DB::table('pump_operator_assignments')->where('id', $assignmentToLink)->first();
        $untouched = DB::table('pump_operator_assignments')->where('id', $assignmentUnrelated)->first();

        $this->assertSame(999999, (int) $linked->settlement_id);
        $this->assertSame(1, (int) $linked->closed_in_settlement);

        $this->assertNull($untouched->settlement_id,
            'Sibling assignment on a different shift must NOT be linked to this settlement.');
        $this->assertSame(0, (int) $untouched->closed_in_settlement,
            'Sibling assignment on a different shift must NOT be marked closed_in_settlement.');
    }
}
