<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PdOperatorsPumperDayEntriesAllShiftTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function all_shift_filter_returns_day_entries_from_every_shift(): void
    {
        $this->useIsolatedPetroBusiness();

        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $firstShiftId = $this->seedPetroShift();
        $secondShiftId = $this->seedPetroShift();

        $this->seedClosedDayEntry((int) $pump->id, $firstShiftId, 100, 150, 50, 500);
        $this->seedClosedDayEntry((int) $pump->id, $secondShiftId, 200, 260, 60, 600);
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

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

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$firstShiftId, $secondShiftId],
            $rows->pluck('shift_id')->map(fn ($shiftId) => (int) $shiftId)->all()
        );
    }

    /** @test */
    public function all_shift_filter_returns_cash_payment_rows_without_ajax_error(): void
    {
        $this->useIsolatedPetroBusiness();

        $shiftId = $this->seedPetroShift();
        $this->seedCashPayment($shiftId, 125);
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

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
        $this->assertSame('cash_payment', $rows->first()['row_type']);
        $this->assertSame($shiftId, (int) $rows->first()['shift_id']);
    }

    /** @test */
    public function all_shift_filter_scopes_payment_detail_joins_to_the_payment_business(): void
    {
        $this->useIsolatedPetroBusiness();

        $shiftId = $this->seedPetroShift();
        $collectionNo = 'CREDIT-' . uniqid();
        $this->seedCreditPayment($shiftId, 175, $collectionNo);
        DB::table('settlement_credit_sale_payments')->insert($this->creditSalePaymentData(999999, 175, $collectionNo));
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

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
        $this->assertSame($this->businessId, (int) $rows->first()['business_id']);
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

    private function seedClosedDayEntry(int $pumpId, int $shiftId, float $startingMeter, float $closingMeter, float $soldLitres, float $amount): void
    {
        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => $startingMeter,
            'closing_meter' => $closingMeter,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
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

        $data = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => $pumpId,
            'date' => now()->toDateString(),
            'starting_meter' => $startingMeter,
            'closing_meter' => $closingMeter,
            'testing_ltr' => 0,
            'sold_ltr' => $soldLitres,
            'amount' => $amount,
            'settlement_no' => null,
            'settlement_datetime' => null,
            'settlement_added_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['shift_id' => $shiftId, 'closed_in_settlement' => 0, 'pump_no' => '', 'time' => now()->format('H:i:s')] as $column => $value) {
            if (Schema::hasColumn('pumper_day_entries', $column)) {
                $data[$column] = $value;
            }
        }

        DB::table('pumper_day_entries')->insert($data);
    }

    private function seedCashPayment(int $shiftId, float $amount): void
    {
        DB::table('pump_operator_payments')->insert([
            'business_id' => $this->businessId,
            'date_and_time' => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'cash',
            'payment_amount' => $amount,
            'collection_form_no' => 'CASH-' . uniqid(),
            'settlement_no' => null,
            'shift_id' => $shiftId,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedCreditPayment(int $shiftId, float $amount, string $collectionNo): void
    {
        DB::table('pump_operator_payments')->insert([
            'business_id' => $this->businessId,
            'date_and_time' => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'credit',
            'payment_amount' => $amount,
            'collection_form_no' => $collectionNo,
            'settlement_no' => null,
            'shift_id' => $shiftId,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settlement_credit_sale_payments')->insert($this->creditSalePaymentData(
            $this->businessId,
            $amount,
            $collectionNo
        ));
    }

    /** @test */
    public function pumper_day_entries_returns_payment_method_for_rows(): void
    {
        $this->useIsolatedPetroBusiness();

        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = $this->seedPetroShift();
        $this->seedClosedDayEntry((int) $pump->id, $shiftId, 100, 150, 50, 500);
        $this->seedCashPayment($shiftId, 125);

        // Seed card payment and account
        $accountId = DB::table('accounts')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Visa Master',
            'account_number' => '1234',
            'created_by' => $this->userId,
            'account_type_id' => null,
            'is_closed' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $collectionNo = 'CARD-' . uniqid();
        DB::table('pump_operator_payments')->insert([
            'business_id' => $this->businessId,
            'date_and_time' => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'card',
            'payment_amount' => 150,
            'collection_form_no' => $collectionNo,
            'settlement_no' => null,
            'shift_id' => $shiftId,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('daily_cards')->insert([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'amount' => 150,
            'collection_no' => $collectionNo,
            'card_type' => $accountId,
            'slip_no' => 'SLIP-123',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();

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

        $dayEntryRow = $rows->firstWhere('row_type', 'day_entry');
        $cashRow = $rows->firstWhere('row_type', 'cash_payment');
        $cardRow = $rows->firstWhere('row_type', 'card_payment');

        $this->assertNotNull($dayEntryRow);
        $this->assertNotNull($cashRow);
        $this->assertNotNull($cardRow);

        $this->assertSame('-', $dayEntryRow['payment_method']);
        $this->assertSame('Cash', $cashRow['payment_method']);
        $this->assertSame('Visa Master', $cardRow['payment_method']);
    }


    /** @test */
    public function pumper_day_entries_view_contains_payment_method_header(): void
    {
        $this->useIsolatedPetroBusiness();
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();
        $user->is_pump_operator = 1;
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->save();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/pumper-dashboard/pump-operators/pumper-day-entries?only_pumper=1');

        $response->assertOk();
        $response->assertSee('Payment Method');
    }

    /** @test */
    public function pumper_payment_summary_operator_dropdown_does_not_have_all_placeholder_when_only_pumper(): void
    {
        $this->useIsolatedPetroBusiness();
        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();
        $user->is_pump_operator = 1;
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->save();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/pumper-dashboard/pump-operator-payments?only_pumper=1');

        $response->assertOk();
        $response->assertDontSee('<option value="">All</option>', false);
    }

    /** @test */
    public function pumper_close_shift_buttons_are_disabled_when_shift_is_closed(): void
    {
        $this->useIsolatedPetroBusiness();
        
        // Seed a closed shift (status = 2) for this operator
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pump = DB::table('pumps')->where('business_id', $this->businessId)->first();
        if ($pump) {
            DB::table('pump_operator_assignments')->insert([
                'business_id' => $this->businessId,
                'pump_id' => $pump->id,
                'pump_operator_id' => $this->pumpOperatorId,
                'starting_meter' => 100,
                'closing_meter' => 120,
                'date_and_time' => now(),
                'status' => 'close',
                'assigned_by' => $this->userId,
                'shift_id' => $shiftId,
                'shift_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->enablePetroModuleForRoute();

        $user = User::where('business_id', $this->businessId)->firstOrFail();
        $user->is_pump_operator = 1;
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->save();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/pumper-dashboard/closing-shift?only_pumper=1');

        $response->assertOk();
        $response->assertSee('id="payment_btn_closing"', false);
        $response->assertSee('id="othersale_btn_closing"', false);
        $response->assertSee('pointer-events: none; opacity: 0.5;', false);
        $response->assertSee('tabindex="-1"', false);
    }

    private function creditSalePaymentData(int $businessId, float $amount, string $collectionNo): array
    {
        return [
            'business_id' => $businessId,
            'customer_id' => $this->contactId,
            'product_id' => $this->productId,
            'pump_operator_id' => $this->pumpOperatorId,
            'order_number' => 'ORD-' . uniqid(),
            'order_date' => now()->toDateString(),
            'price' => $amount,
            'discount' => 0,
            'qty' => 1,
            'amount' => $amount,
            'sub_total' => $amount,
            'total_discount' => 0,
            'is_from_pumper' => 1,
            'collection_form_no' => $collectionNo,
            'bill_number' => 'BILL-' . uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
