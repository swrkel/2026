<?php

namespace Tests\Feature\Petro;

use App\AccountTransaction;
use App\ContactLedger;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Http\Controllers\SettlementController;

class RealTimeEntriesLedgerBookSyncTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\Schema::connection('system')->hasTable('subscriptions')) {
            DB::connection('system')->table('subscriptions')
                ->where('business_id', $this->businessId)
                ->update([
                    'package_details' => json_encode([
                        'petro_pd_module' => 1,
                        'real_time_entries' => 1,
                    ])
                ]);
        }
    }

    /** @test */
    public function new_sync_settings_can_be_stored_and_rendered(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->post('/real-time-entries/store-settings', [
                'is_admin' => 1,
                'created_at' => now()->toDateTimeString(),
                'user_added' => $user->username,
                'show_bulk_pumps' => 'yes',
                'meter_sales_compulsory' => 'yes',
                'enter_cash_denominations' => 'yes',
                'card_amount_to_enter' => 'bulk',
                'enter_card_numbers' => 'yes',
                'real_time_update_customer_ledger' => 'yes',
                'real_time_update_account_books' => 'yes',
            ]);

        $response->assertRedirect();

        // Assert they are in the database settings JSON
        $op = PumpOperator::where('business_id', $this->businessId)
            ->whereNotNull('dashboard_settings')
            ->first();

        $this->assertNotNull($op);
        $settings = json_decode($op->dashboard_settings, true);
        $this->assertEquals('yes', $settings['real_time_update_customer_ledger']);
        $this->assertEquals('yes', $settings['real_time_update_account_books']);
    }

    /** @test */
    public function save_credit_sale_syncs_conditionally_based_on_settings(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // 1. Set settings to 'no'
        PumpOperator::where('business_id', $this->businessId)
            ->update([
                'dashboard_settings' => json_encode([
                    'real_time_update_customer_ledger' => 'no',
                    'real_time_update_account_books' => 'no',
                ])
            ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->postJson('/real-time-entries/save-credit-sale', [
                'amount' => 450,
                'customer_id' => $this->contactId,
                'shift_number' => '11',
                'transaction_date' => now()->toDateString(),
            ]);

        $response->assertOk();

        // Assert no AccountTransaction or ContactLedger exists for amount 450
        $txCount = AccountTransaction::where('amount', 450)->count();
        $ledgerCount = ContactLedger::where('contact_id', $this->contactId)->where('amount', 450)->count();
        $this->assertEquals(0, $txCount);
        $this->assertEquals(0, $ledgerCount);

        // 2. Set settings to 'yes'
        PumpOperator::where('business_id', $this->businessId)
            ->update([
                'dashboard_settings' => json_encode([
                    'real_time_update_customer_ledger' => 'yes',
                    'real_time_update_account_books' => 'yes',
                ])
            ]);

        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->postJson('/real-time-entries/save-credit-sale', [
                'amount' => 750,
                'customer_id' => $this->contactId,
                'shift_number' => '11',
                'transaction_date' => now()->toDateString(),
            ]);

        $response2->assertOk();

        // Assert AccountTransaction and ContactLedger exist for amount 750
        $tx = AccountTransaction::where('amount', 750)->first();
        $ledger = ContactLedger::where('contact_id', $this->contactId)->where('amount', 750)->first();
        $this->assertNotNull($tx);
        $this->assertNotNull($ledger);
    }

    /** @test */
    public function finalizing_settlement_prevents_duplicate_entries_if_sync_enabled(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $cardGroup = \App\AccountGroup::firstOrCreate([
            'business_id' => $this->businessId,
            'name' => 'Card',
        ]);
        $visaAccount = \App\Account::create([
            'business_id' => $this->businessId,
            'name' => 'VISA Test Account',
            'account_number' => '12345',
            'asset_type' => $cardGroup->id,
            'created_by' => $user->id,
        ]);

        // Set settings to 'yes'
        PumpOperator::where('business_id', $this->businessId)
            ->update([
                'dashboard_settings' => json_encode([
                    'real_time_update_customer_ledger' => 'yes',
                    'real_time_update_account_books' => 'yes',
                ])
            ]);

        // Create shift
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 0, // open
            'shift_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Save real-time card payment (instantly syncs to AccountTransaction)
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->postJson('/real-time-entries/save-card', [
                'amount' => 900,
                'card_type' => $visaAccount->id,
                'slip_no' => 'SLIP-900',
                'shift_number' => '12',
                'transaction_date' => now()->toDateString(),
            ]);

        $response->assertOk();
        $rtPaymentId = $response->json('payment_id');
        $this->assertNotNull($rtPaymentId);

        // Verify it was indeed synced instantly (1 entry)
        $this->assertEquals(1, AccountTransaction::where('amount', 900)->count());

        // Create draft settlement in database
        $settlementNo = 'ST-TEST-' . uniqid();
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftId]),
            'status' => 1, // draft
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link card payment to the settlement
        DB::table('settlement_card_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'amount' => 900,
            'customer_id' => $this->contactId,
            'pump_payment_id' => $rtPaymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Setup request to finalize settlement
        $request = \Illuminate\Http\Request::create('/petro/settlement', 'POST', [
            'settlement_no' => $settlementNo,
            'no_change' => 0,
            'shift_ids' => [$shiftId],
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        // Acting as user with session populated
        $this->actingAs($user)->withSession([
            'business.id' => $this->businessId,
            'user.business_id' => $this->businessId,
            'user.id' => $user->id,
        ]);

        // Finalize settlement
        app(SettlementController::class)->store($request, app(\App\Http\Controllers\ContactController::class));

        // Assert that the AccountTransaction entries were NOT duplicated (still exactly 1 entry of 900)
        $this->assertEquals(1, AccountTransaction::where('amount', 900)->count());
    }
}
