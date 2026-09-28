<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PdAddPaymentMismatchedSettlementTest extends PetroTestCase
{
    use DatabaseTransactions;

    public function test_mismatched_settlement_forces_resolution_of_correct_settlement(): void
    {
        // Using standard PetroTestCase fixtures

        // Seed Shift 3 (previous) and Shift 4 (current)
        $shift3Id = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2, // closed
            'shift_date' => now()->subDay(),
            'closed_time' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $shift4Id = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2, // closed
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed assignments
        $pump = DB::table('pumps')->where('business_id', $this->businessId)->first();
        
        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 150,
            'date_and_time' => now()->subDay(),
            'close_date_and_time' => now()->subDay(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shift3Id,
            'shift_number' => '3',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 150,
            'closing_meter' => 200,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shift4Id,
            'shift_number' => '4',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed a settlement for Shift 3
        $locationId = (int) DB::table('business_locations')->where('business_id', $this->businessId)->value('id');
        $settlement3Id = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST3',
            'business_id' => $this->businessId,
            'transaction_date' => now()->subDay()->toDateString(),
            'finish_date' => now()->subDay()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([$shift3Id]),
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        // Seed a shortage payment for Shift 3's settlement
        DB::table('settlement_shortage_payments')->insert([
            'settlement_no' => $settlement3Id,
            'business_id' => $this->businessId,
            'amount' => 500.00,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->enablePetroModuleForRoute();
        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);
        session()->put([
            'business.id' => $this->businessId,
            'user.business_id' => $this->businessId,
            'user.id' => $user->id,
            'currency' => $this->currencySessionData(),
        ]);

        $request = \Illuminate\Http\Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no' => 'PDST3',
            'type' => 'settlement_pd',
            'shift_ids' => (string) $shift4Id,
            'pump_operator_id' => $this->pumpOperatorId,
            'operator_id' => $this->pumpOperatorId,
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view = app(\Modules\Petro\Http\Controllers\AddPaymentController::class)->create($request);
        $shortagePayments = $view->getData()['settlement_shortage_payments'];

        $this->assertEquals(0, $shortagePayments->count(), 'Shortage payment from Shift 3 should not be present in Shift 4 view');
    }

    private function useIsolatedPetroBusiness(): void
    {
        $suffix = uniqid('pd-msm-');
        $currencyId = $this->ensureTestCurrency();

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'PD',
            'last_name' => 'Msm',
            'username' => $suffix,
            'email' => $suffix . '@example.test',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'PD Mismatched Settlement Test',
            'currency_id' => $currencyId,
            'owner_id' => $userId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $userId)->update(['business_id' => $businessId]);

        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $businessId,
            'name' => 'PD Msm Location',
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'name' => 'PD Msm Fuel',
            'business_id' => $businessId,
            'type' => 'single',
            'unit_id' => 1,
            'sku' => $suffix . '-fuel',
            'tax_type' => 'inclusive',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pumpId = DB::table('pumps')->insertGetId([
            'business_id' => $businessId,
            'pump_name' => 'PD Msm Pump',
            'location_id' => $locationId,
            'fuel_type' => 'Fuel',
            'installation_date' => now()->toDateString(),
            'pump_no' => 'PD-MSM-1',
            'image_link' => '',
            'product_id' => $productId,
            'fuel_tank_id' => 1,
            'qty' => '0',
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $operatorId = DB::table('pump_operators')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'pump_id' => $pumpId,
            'name' => 'PD Msm Operator',
            'cnic' => '',
            'address' => '',
            'dob' => '2000-01-01',
            'mobile' => '',
            'assigned_pump_id' => $pumpId,
            'status' => 1,
            'commission_type' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->businessId = $businessId;
        $this->pumpOperatorId = $operatorId;
        $this->productId = $productId;
        $this->userId = $userId;
    }

    private function enablePetroModuleForRoute(): void
    {
        if (! Schema::connection('system')->hasTable('subscriptions')) {
            return;
        }

        DB::connection('system')->table('subscriptions')->insert([
            'business_id' => $this->businessId,
            'package_id' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'trial_end_date' => null,
            'end_date' => now()->addYears(2)->toDateString(),
            'package_price' => 0,
            'package_details' => json_encode([
                'enable_petro_module' => 1,
                'petro_pd_module' => 1,
            ]),
            'created_id' => $this->userId,
            'paid_via' => null,
            'payment_transaction_id' => null,
            'status' => 'approved',
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
