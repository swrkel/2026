<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Petro\Entities\PumperDayEntry;

class DailyPumpStatusNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a business and user
        $business = Business::create(['name' => 'Test Business']);
        $user = User::create([
            'first_name' => 'Admin',
            'username' => 'admin',
            'password' => \Hash::make('secret'),
            'business_id' => $business->id
        ]);
        
        $this->actingAs($user);
    }

    public function test_bulk_assignment_preserves_tab_state()
    {
        $response = $this->post('/petro/pump-operator-assignment/store-bulk', [
            'tab' => 'daily_pump_status',
            'pump_operators' => [1],
            'pumps' => [1],
            'shift_id' => 1
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status.tab', 'daily_pump_status');
    }

    public function test_pumper_day_entry_update_preserves_tab_state()
    {
        $business_id = Auth::user()->business_id;
        $entry = PumperDayEntry::create([
            'business_id' => $business_id,
            'pump_operator_id' => 1,
            'pump_id' => 1,
            'starting_meter' => 100,
            'closing_meter' => 200,
            'date' => now()
        ]);

        $response = $this->put('/petro/pumper-day-entries/' . $entry->id, [
            'tab' => 'daily_pump_status',
            'date' => now()->format('Y-m-d'),
            'pump_operator_id' => 1,
            'pump_id' => 1,
            'starting_meter' => 100,
            'closing_meter' => 250,
            'testing_ltr' => 0,
            'sold_ltr' => 150,
            'amount' => 1500
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status.tab', 'daily_pump_status');
    }

    public function test_add_settlement_no_preserves_tab_state()
    {
        $business_id = Auth::user()->business_id;
        $entry = PumperDayEntry::create([
            'business_id' => $business_id,
            'pump_operator_id' => 1,
            'pump_id' => 1,
            'starting_meter' => 100,
            'closing_meter' => 200,
            'date' => now()
        ]);

        $response = $this->post('/petro/post-add-settlement-no/' . $entry->id, [
            'tab' => 'daily_pump_status',
            'settlement_no' => 'SET-001'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status.tab', 'daily_pump_status');
    }

    public function test_pump_operator_assignment_store_preserves_tab_state()
    {
        $business_id = Auth::user()->business_id;
        $shiftDate = Carbon::now();
        $dob = Carbon::now()->subYears(25);
        $pumpId = DB::table('pumps')->insertGetId([
            'business_id' => $business_id,
            'pump_name' => 'Test Pump',
            'location_id' => 1,
            'fuel_type' => 'Diesel',
            'installation_date' => $shiftDate->toDateString(),
            'pump_no' => 'P1',
            'image_link' => '',
            'storage_tank' => 'Tank 1',
            'starting_meter' => 0,
            'last_meter_reading' => 0,
            'temp_meter_reading' => 0,
            'product_id' => 1,
            'fuel_tank_id' => 1,
            'qty' => '0',
            'checkk' => 1,
            'testing' => '1',
            'transaction_date' => $shiftDate->toDateString(),
            'bulk_sale_meter' => 0,
            'created_at' => $shiftDate,
            'updated_at' => $shiftDate,
        ]);

        $pumpOperatorId = DB::table('pump_operators')->insertGetId([
            'business_id' => $business_id,
            'location_id' => 1,
            'pump_id' => $pumpId,
            'name' => 'Test Operator',
            'cnic' => '1234567890123',
            'address' => 'Test Address',
            'dob' => $dob->toDateString(),
            'mobile' => '0123456789',
            'assigned_pump_id' => $pumpId,
            'commission_type' => 'none',
            'commission_ap' => 0,
            'short_amount' => 0,
            'excess_amount' => 0,
            'active' => 1,
            'dashboard_settings' => json_encode([]),
            'is_default' => 0,
            'can_fullscreen' => 0,
        ]);

        $response = $this->post('/petro/pump-operator-assignment', [
            'pump_id' => $pumpId,
            'pump_operator_id' => $pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 200,
            'status' => '1',
            'tab' => 'daily_pump_status'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status.tab', 'daily_pump_status');
    }
}
