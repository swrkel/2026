<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PumperDashboardOtherSalesPageTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_other_sales_page_contains_please_select_placeholder(): void
    {
        // Seed assignment for the current operator/user to allow access to the dashboard/other sales
        $pump = DB::table('pumps')->where('business_id', $this->businessId)->first();
        if (!$pump) {
            $this->markTestSkipped('No pumps found for business.');
        }

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1, // open
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
            'shift_id' => $shiftId,
            'shift_number' => '10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Find the user associated with this operator or construct a user
        $user = User::where('business_id', $this->businessId)->firstOrFail();
        
        // Ensure user has correct pump operator ID for dashboard permission
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->save();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/pumper-dashboard/pump-operator-payments/othersale');

        $response->assertOk();
        $response->assertSee('Please Select');
    }
}
