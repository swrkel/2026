<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class PumperDashboardClosingShiftButtonsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_dropdown_status_and_buttons_disable_on_closing_shift_page(): void
    {
        $pump = DB::table('pumps')->where('business_id', $this->businessId)->first();
        if (!$pump) {
            $this->markTestSkipped('No pumps found.');
        }

        // 1. Seed a shift with status 1 (Draft/Open in Petro)
        $shiftId1 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1,
            'shift_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 100,
            'status' => 'open',
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'shift_id' => $shiftId1,
            'shift_number' => '91',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where('business_id', $this->businessId)->firstOrFail();
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->is_pump_operator = 1;
        $user->save();

        // Check response when active shift is status 1
        $response1 = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/pumper-dashboard/closing-shift?only_pumper=1');

        $response1->assertOk();
        
        // Assert status mapping: status 1 must be Open, not Closed
        $response1->assertSee('Shift 91 (Open)');
        $response1->assertDontSee('Shift 91 (Closed)');

        // Assert buttons are NOT disabled
        $response1->assertDontSee('pointer-events: none; opacity: 0.5;');

        // 2. Seed another shift with status 2 (Closed)
        $shiftId2 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 100,
            'status' => 'open',
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'shift_id' => $shiftId2,
            'shift_number' => '92',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Check response when active shift is status 2
        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/pumper-dashboard/closing-shift?only_pumper=1');

        $response2->assertOk();
        
        // Assert status mapping: status 2 must be Closed
        $response2->assertSee('Shift 92 (Closed)');

        // Assert buttons are disabled
        $response2->assertSee('pointer-events: none; opacity: 0.5;');
    }
}
