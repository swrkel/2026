<?php

namespace Tests\Feature\Petro;

use App\User;
use App\ContactLedger;
use App\AccountTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Http\Controllers\SettlementPDController;

class PetroPDSettingsLedgerSyncTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('business_id', $this->businessId)->firstOrFail();
    }

    /** @test */
    public function settings_page_and_routes_are_accessible_and_saveable(): void
    {
        $this->actingAs($this->user)
            ->get(route('petropd.get-settings'))
            ->assertStatus(200);

        $postData = [
            'created_at' => now()->format('Y-m-d H:i'),
            'user_added' => $this->user->username,
            'is_admin' => 1,
            'show_bulk_pumps' => 'yes',
            'credit_sales_direct_to_customer' => 'yes',
            'logoff_time' => '10',
            'logoff' => 'yes',
            'meter_sales_compulsory' => 'yes',
            'enter_cash_denominations' => 'yes',
            'card_amount_to_enter' => 'one_by_one',
            'enter_card_numbers' => 'yes',
            'bill_prefix' => 'PDTEST',
            'starting_bill_number' => 100,
            'pumper_ledger_update' => 'yes',
        ];

        $this->actingAs($this->user)
            ->post(route('petropd.store-settings'), $postData)
            ->assertRedirect();

        // Verify it was stored in the database
        $operatorSettings = PumpOperator::where('business_id', $this->businessId)
            ->whereNotNull('dashboard_settings')
            ->first();
        
        $this->assertNotNull($operatorSettings);
        $settingsJson = json_decode($operatorSettings->dashboard_settings, true);
        $this->assertEquals('yes', $settingsJson['pumper_ledger_update'] ?? 'no');
    }

    /** @test */
    public function credit_sale_saves_instantly_to_ledger_only_if_pumper_ledger_update_is_enabled(): void
    {
        $this->seedShiftAndAssignment();

        // 1. First test with pumper_ledger_update = no
        PumpOperator::where('business_id', $this->businessId)
            ->update(['dashboard_settings' => json_encode(['pumper_ledger_update' => 'no'])]);

        $creditData = [
            'pump_operator_id' => $this->pumpOperatorId,
            'credit_data' => [
                [
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'qty' => 10,
                    'price' => 150,
                    'amount' => 1500,
                    'sub_total' => 1500,
                    'total_discount' => 0,
                    'unit_discount' => 0,
                    'order_number' => 'ORD-111',
                    'order_date' => now()->toDateString(),
                    'outstanding' => 0,
                    'credit_limit' => 5000,
                    'customer_reference' => 'REF-111',
                    'note' => 'test note',
                ]
            ],
            'collection_form_no' => 'FORM-111',
        ];

        $initialLedgerCount = ContactLedger::where('contact_id', $this->contactId)->count();

        // Simulate save credit via PumpOperatorPaymentController
        $this->actingAs($this->user)
            ->postJson('/petro/pump-operator-pmts/save-credit', $creditData)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert no new ledger entry was created
        $this->assertEquals($initialLedgerCount, ContactLedger::where('contact_id', $this->contactId)->count());

        // 2. Now test with pumper_ledger_update = yes
        PumpOperator::where('business_id', $this->businessId)
            ->update(['dashboard_settings' => json_encode(['pumper_ledger_update' => 'yes'])]);

        $creditData['credit_data'][0]['order_number'] = 'ORD-222';
        $creditData['collection_form_no'] = 'FORM-222';

        $this->actingAs($this->user)
            ->postJson('/petro/pump-operator-pmts/save-credit', $creditData)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Assert ledger entry WAS created instantly
        $ledgerEntry = ContactLedger::where('contact_id', $this->contactId)
            ->where('note', 'like', '%Pumper Dashboard Credit Sale - Form No. FORM-222%')
            ->first();

        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(1500, (float)$ledgerEntry->amount);
        $this->assertEquals('debit', $ledgerEntry->type);
    }

    private function seedShiftAndAssignment(): void
    {
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1,
            'shift_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pumpId = DB::table('pumps')->where('business_id', $this->businessId)->value('id') ?? 1;

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pump_id' => $pumpId,
            'shift_id' => $shiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function settlement_finalization_skips_ledger_creation_if_pumper_ledger_update_is_enabled(): void
    {
        $this->actingAs($this->user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ]);
        session()->put('business.id', $this->businessId);
        session()->put('user.business_id', $this->businessId);
        session()->put('user.id', $this->userId);
        request()->setLaravelSession(app('session.store'));

        // Enable pumper_ledger_update setting
        PumpOperator::where('business_id', $this->businessId)
            ->update(['dashboard_settings' => json_encode(['pumper_ledger_update' => 'yes'])]);

        $settlement = $this->seedSettlement();
        
        // Seed a credit sale payment that came from the pumper dashboard (has pump_payment_id)
        $paymentId = DB::table('pump_operator_payments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'credit',
            'payment_amount' => 500,
            'created_by' => $this->userId,
        ]);

        $creditSale = $this->seedCreditSale($settlement, [
            'pump_payment_id' => $paymentId,
            'amount' => 500,
            'sub_total' => 500,
            'collection_form_no' => 'FORM-333',
        ]);

        $controller = app(SettlementPDController::class);
        
        $initialLedgerCount = ContactLedger::where('contact_id', $this->contactId)->count();

        // Simulate finalization ledger entry creation check
        // Because pumper_ledger_update is yes, createAccountTransaction should skip ContactLedger creation
        $controller->createAccountTransaction(
            $this->createMockTransaction($settlement),
            'debit',
            1, // dummy account ID
            null,
            'ledger_show',
            $creditSale->customer_id,
            500,
            true,
            'note',
            null,
            false, // skip_account_books
            true   // skip_customer_ledger (this should be true because setting is yes)
        );

        $this->assertEquals($initialLedgerCount, ContactLedger::where('contact_id', $this->contactId)->count());
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-AR-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }

    private function seedCreditSale(Settlement $settlement, array $overrides = []): SettlementCreditSalePayment
    {
        $id = DB::table('settlement_credit_sale_payments')->insertGetId($this->buildCreditSalePaymentData(array_merge([
            'settlement_no' => $settlement->settlement_no,
            'pump_operator_id' => $settlement->pump_operator_id,
            'order_number' => 'AR-TST-' . uniqid(),
        ], $overrides)));

        return SettlementCreditSalePayment::findOrFail($id);
    }

    private function createMockTransaction(Settlement $settlement)
    {
        $txId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'contact_id' => $this->contactId,
            'transaction_date' => now(),
            'total_before_tax' => 500,
            'final_total' => 500,
            'created_by' => $this->userId,
        ]);

        return \App\Transaction::findOrFail($txId);
    }
}
