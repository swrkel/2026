<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PdSettlementShiftDropdownDisabledTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_unselected_shift_numbers_are_disabled_in_dropdown(): void
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

        // Seed 2 shifts that are closed
        $shiftId1 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2, // closed
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shiftId2 = DB::table('petro_shifts')->insertGetId([
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
                'shift_number' => 801,
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
                'shift_number' => 802,
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

        // The oldest pending shift (shiftId1 / 801) should be selected (FIFO).
        // Since shift 801 is selected:
        // Option 801 should NOT have "disabled"
        // Option 802 SHOULD have "disabled"
        
        $html = $response->getContent();
        
        $this->assertStringContainsString('value="' . $shiftId1 . '" selected', $html);
        $this->assertStringContainsString('value="' . $shiftId2 . '" disabled', $html);
    }
}
