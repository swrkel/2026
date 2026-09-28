<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PumperDayEntriesSettlementStatusTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function settlement_no_is_hidden_for_draft_settlements_and_visible_for_saved_settlements(): void
    {
        $this->useIsolatedPetroBusiness();

        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = $this->seedPetroShift();

        // 1. Create a draft settlement (status = 1)
        $settlementId = DB::table('settlements')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'settlement_no' => 'ST-TEST-DRAFT',
            'status' => 1, // draft
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create an assignment linked to this draft settlement
        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 150,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => $settlementId,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => $shiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create a pumper day entry linked to this assignment
        $pdeData = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
            'starting_meter' => 100,
            'closing_meter' => 150,
            'testing_ltr' => 0,
            'sold_ltr' => 50,
            'amount' => 500,
            'settlement_no' => $settlementId,
            'settlement_datetime' => null,
            'settlement_added_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['shift_id' => $shiftId, 'closed_in_settlement' => 0, 'pump_no' => '', 'time' => now()->format('H:i:s')] as $column => $value) {
            if (Schema::hasColumn('pumper_day_entries', $column)) {
                $pdeData[$column] = $value;
            }
        }

        DB::table('pumper_day_entries')->insert($pdeData);
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // 4. Send request and verify that settlement_no is hidden (draft)
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/pumper-day-entries?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'shift_id' => '',
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        
        $this->assertCount(1, $rows);
        $this->assertEquals('-', $rows->first()['settlement_no']);

        // 5. Update settlement status to 0 (saved/completed)
        DB::table('settlements')->where('id', $settlementId)->update(['status' => 0]);

        // 6. Send request and verify that settlement_no is now visible (saved)
        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/pumper-day-entries?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'shift_id' => '',
            ]));

        $response2->assertOk();
        $rows2 = collect($response2->json('data'));
        
        $this->assertCount(1, $rows2);
        $this->assertStringContainsString('ST-TEST-DRAFT', $rows2->first()['settlement_no']);
    }

    /** @test */
    public function pumper_dashboard_settlement_no_is_hidden_for_draft_settlements_and_visible_for_saved_settlements(): void
    {
        $this->useIsolatedPetroBusiness();

        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = $this->seedPetroShift();

        // 1. Create a draft settlement (status = 1)
        $settlementId = DB::table('settlements')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'settlement_no' => 'ST-TEST-DRAFT-2',
            'status' => 1, // draft
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create an assignment linked to this draft settlement
        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 150,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => $settlementId,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => $shiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create a pumper day entry linked to this assignment
        $pdeData = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
            'starting_meter' => 100,
            'closing_meter' => 150,
            'testing_ltr' => 0,
            'sold_ltr' => 50,
            'amount' => 500,
            'settlement_no' => $settlementId,
            'settlement_datetime' => null,
            'settlement_added_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['shift_id' => $shiftId, 'closed_in_settlement' => 0, 'pump_no' => '', 'time' => now()->format('H:i:s')] as $column => $value) {
            if (Schema::hasColumn('pumper_day_entries', $column)) {
                $pdeData[$column] = $value;
            }
        }

        DB::table('pumper_day_entries')->insert($pdeData);
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // 4. Send request and verify that settlement_no is hidden (draft)
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/pumper-dashboard/pump-operators/pumper-day-entries?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'shift_id' => '',
            ]));

        $response->assertOk();
        $rows = collect($response->json('data'));
        
        $this->assertCount(1, $rows);
        $this->assertEquals('-', $rows->first()['settlement_no']);

        // 5. Update settlement status to 0 (saved/completed)
        DB::table('settlements')->where('id', $settlementId)->update(['status' => 0]);

        // 6. Send request and verify that settlement_no is now visible (saved)
        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/pumper-dashboard/pump-operators/pumper-day-entries?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'shift_id' => '',
            ]));

        $response2->assertOk();
        $rows2 = collect($response2->json('data'));
        
        $this->assertCount(1, $rows2);
        $this->assertStringContainsString('ST-TEST-DRAFT-2', $rows2->first()['settlement_no']);
    }


    private function seedPetroShift(): int
    {
        $data = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('petro_shifts', 'work_shift_id')) {
            $data['work_shift_id'] = null;
        }

        return DB::table('petro_shifts')->insertGetId($data);
    }

    private function useIsolatedPetroBusiness(): void
    {
        $suffix = uniqid('pd-all-');
        $currencyId = $this->ensureTestCurrency();

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'PD',
            'last_name' => 'All',
            'username' => $suffix,
            'email' => $suffix . '@example.test',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'PD All Shift Test',
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
            'name' => 'PD All Location',
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
            'name' => 'PD All Fuel',
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
            'pump_name' => 'PD All Pump',
            'location_id' => $locationId,
            'fuel_type' => 'Fuel',
            'installation_date' => now()->toDateString(),
            'pump_no' => 'PD-ALL-1',
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
            'name' => 'PD All Operator',
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
                'pump_operator_dashboard' => 1,
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
