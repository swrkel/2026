<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class AvailablePumpOperatorsDropdownTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_active_shift_operators_are_excluded_from_assign_pumps_dropdown(): void
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

        // Drop current assignments to ensure a clean slate
        DB::table('pump_operator_assignments')->where('business_id', $this->businessId)->delete();

        // 1. Create two operators
        $operator1Id = DB::table('pump_operators')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'pump_id' => $pumpId,
            'name' => 'Operator with Open Shift',
            'dob' => '2000-01-01',
            'status' => 1,
            'commission_type' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $operator2Id = DB::table('pump_operators')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'pump_id' => $pumpId,
            'name' => 'Operator with No Open Shift',
            'dob' => '2000-01-01',
            'status' => 1,
            'commission_type' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Assign operator1 to an open shift
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $operator1Id,
            'status' => 0, // open
            'shift_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $operator1Id,
            'starting_meter' => 10,
            'date_and_time' => now(),
            'status' => 'open',
            'assigned_by' => $this->userId,
            'shift_id' => $shiftId,
            'shift_number' => 999,
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
            ->get('/petro/pump-operator-assignment/create');

        $response->assertOk();

        $html = $response->getContent();

        // The dropdown should NOT contain the operator with an open shift (Operator 1)
        // but SHOULD contain the operator without an open shift (Operator 2)
        $this->assertStringNotContainsString('value="' . $operator1Id . '"', $html);
        $this->assertStringContainsString('value="' . $operator2Id . '"', $html);
    }
}
