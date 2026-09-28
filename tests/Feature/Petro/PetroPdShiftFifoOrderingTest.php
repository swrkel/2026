<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class PetroPdShiftFifoOrderingTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_autoloads_oldest_shift_numerically_fifo(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        // Seed 2 shifts closed
        // ShiftId1 has smaller ID (500) but larger shift number (999)
        $shiftId1 = DB::table('petro_shifts')->insertGetId([
            'id' => 500,
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2, // closed
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ShiftId2 has larger ID (501) but smaller shift number (998)
        $shiftId2 = DB::table('petro_shifts')->insertGetId([
            'id' => 501,
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2, // closed
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert closed assignments to make them pending shifts
        DB::table('pump_operator_assignments')->insert([
            [
                'business_id' => $this->businessId,
                'pump_id' => $pumpId,
                'pump_operator_id' => $this->pumpOperatorId,
                'starting_meter' => 0,
                'closing_meter' => 10,
                'date_and_time' => now(),
                'close_date_and_time' => now(),
                'status' => 'close',
                'settlement_id' => null,
                'assigned_by' => $this->userId,
                'is_confirmed' => 1,
                'confirmed_at' => now(),
                'is_manually_closed' => 1,
                'closed_in_settlement' => 0,
                'shift_id' => $shiftId1,
                'shift_number' => 999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $this->businessId,
                'pump_id' => $pumpId,
                'pump_operator_id' => $this->pumpOperatorId,
                'starting_meter' => 0,
                'closing_meter' => 10,
                'date_and_time' => now(),
                'close_date_and_time' => now(),
                'status' => 'close',
                'settlement_id' => null,
                'assigned_by' => $this->userId,
                'is_confirmed' => 1,
                'confirmed_at' => now(),
                'is_manually_closed' => 1,
                'closed_in_settlement' => 0,
                'shift_id' => $shiftId2,
                'shift_number' => 998,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/petropd/pd-settlement');

        $response->assertOk();

        $html = $response->getContent();

        // The older shift number numerically (998 / shiftId2) should be selected (FIFO), not shiftId1.
        $this->assertStringContainsString('value="' . $shiftId2 . '" selected', $html);
    }

    /** @test */
    public function test_prefers_active_settlement_over_oldest_pending_globally(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        // Operator A: has shift 3 (shiftIdA) which is oldest globally pending
        $operatorIdA = DB::table('pump_operators')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Operator A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $shiftIdA = DB::table('petro_shifts')->insertGetId([
            'id' => 600,
            'business_id' => $this->businessId,
            'pump_operator_id' => $operatorIdA,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $operatorIdA,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftIdA,
            'shift_number' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Operator B: has shift 4 (shiftIdB) which has an ACTIVE draft settlement
        $operatorIdB = DB::table('pump_operators')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Operator B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $shiftIdB = DB::table('petro_shifts')->insertGetId([
            'id' => 601,
            'business_id' => $this->businessId,
            'pump_operator_id' => $operatorIdB,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST123',
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'pump_operator_id' => $operatorIdB,
            'transaction_date' => date('Y-m-d'),
            'work_shift' => json_encode([(string) $shiftIdB]),
            'status' => 1, // draft
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $operatorIdB,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => $settlementId, // linked to draft
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftIdB,
            'shift_number' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/petropd/pd-settlement');

        $response->assertOk();

        // The JS initialOperatorId should be Operator B (operatorIdB) and initialShiftId should be shiftIdB,
        // because the draft settlement is active and should not be overridden by the oldest pending shift (Operator A, Shift 3).
        $html = $response->getContent();
        $this->assertStringContainsString('const initialOperatorId = ' . json_encode($operatorIdB) . ';', $html);
        $this->assertStringContainsString('const initialShiftId = ' . json_encode($shiftIdB) . ';', $html);
    }

    /** @test */
    public function test_check_prev_settlement_allows_active_draft_shift(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $operatorId = DB::table('pump_operators')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Operator',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'id' => 700,
            'business_id' => $this->businessId,
            'pump_operator_id' => $operatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert active draft settlement
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST700',
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'pump_operator_id' => $operatorId,
            'transaction_date' => date('Y-m-d'),
            'work_shift' => json_encode([(string) $shiftId]),
            'status' => 1, // draft
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $operatorId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => $settlementId, // linked to draft
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Settle a newer shift first? Wait, there is a newer shift which is pending closed
        $newerShiftId = DB::table('petro_shifts')->insertGetId([
            'id' => 701,
            'business_id' => $this->businessId,
            'pump_operator_id' => $operatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $operatorId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $newerShiftId,
            'shift_number' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get("/petro/pdsettlement-pd/check-prev-settlement?shift_id={$shiftId}&pump_operator_id={$operatorId}");

        $response->assertOk();
        $response->assertJson([
            'status' => true,
            'msg' => 'OK'
        ]);
    }

    /** @test */
    public function test_meter_sale_table_shows_loading_spinner(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/petropd/pd-settlement');

        $response->assertOk();
        $this->assertStringContainsString('fa-spinner fa-spin', $response->getContent());
    }
}

