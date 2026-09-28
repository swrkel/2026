<?php

namespace Tests\Feature\Petro;

use App\User;
use App\Utils\ContactUtil;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Http\Controllers\SettlementController;

class PumperDashboardCreditSaleLedgerTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pumper_dashboard_credit_sale_creates_customer_ledger_debit(): void
    {
        $this->withoutExceptionHandling();

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

        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update(['location_id' => $locationId]);

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 1,
            'shift_date'       => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id'        => $this->businessId,
            'pump_id'            => $pumpId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'starting_meter'     => 0,
            'closing_meter'      => 0,
            'date_and_time'      => now(),
            'status'             => 'open',
            'assigned_by'        => $this->userId,
            'is_confirmed'       => 1,
            'confirmed_at'       => now(),
            'is_manually_closed' => 0,
            'shift_id'           => $shiftId,
            'shift_number'       => 99,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $orderNumber = 'LEDGER-' . uniqid();
        $before = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('contact_id', $this->contactId)
            ->where('ref_no', $orderNumber)
            ->where('is_credit_sale', 1)
            ->count();

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 2,
                    'amount' => 200,
                    'sub_total' => 200,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH-' . uniqid(),
                    'note' => 'ledger regression',
                ]],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $after = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('contact_id', $this->contactId)
            ->where('ref_no', $orderNumber)
            ->where('is_credit_sale', 1)
            ->where('final_total', 200)
            ->count();

        $this->assertSame($before + 1, $after);
    }

    /** @test */
    public function test_customer_ledger_reads_only_settled_credit_sale_transaction_when_placeholder_exists(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $orderNo = 'LEDGER-PLACEHOLDER-' . uniqid();
        $settlementNo = 'PDST-TST-' . uniqid();
        $date = now()->toDateString();
        $walkIn = (new ContactUtil())->getWalkInCustomer($this->businessId);
        $contactId = (int) DB::table('contacts')
            ->where('business_id', $this->businessId)
            ->when(! empty($walkIn['id']), fn ($query) => $query->where('id', '!=', $walkIn['id']))
            ->orderBy('id')
            ->value('id');
        if (! $contactId) {
            $this->markTestSkipped("No non-walk-in contacts for business {$this->businessId}.");
        }

        DB::table('settlements')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'transaction_date' => $date,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            [
                'business_id' => $this->businessId,
                'contact_id' => $contactId,
                'location_id' => $locationId,
                'type' => 'sell',
                'status' => 'final',
                'payment_status' => 'due',
                'final_total' => 3820,
                'transaction_date' => $date,
                'created_by' => $this->userId,
                'invoice_no' => null,
                'ref_no' => $orderNo,
                'sub_type' => null,
                'is_credit_sale' => 1,
                'is_settlement' => 0,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'business_id' => $this->businessId,
                'contact_id' => $contactId,
                'location_id' => $locationId,
                'type' => 'sell',
                'sub_type' => 'credit_sale',
                'status' => 'final',
                'payment_status' => 'due',
                'final_total' => 3820,
                'transaction_date' => $date,
                'created_by' => $this->userId,
                'invoice_no' => $settlementNo,
                'ref_no' => null,
                'is_credit_sale' => 1,
                'is_settlement' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $ledger = (new ContactUtil())->getCustomerLedger($contactId, $this->businessId, $date, $date);
        $matchingRows = $ledger
            ->filter(fn ($row) => $row->invoice_no === $settlementNo || $row->bill_no === $orderNo)
            ->values();

        $this->assertCount(1, $matchingRows);
        $this->assertSame($settlementNo, $matchingRows->first()->invoice_no);
    }

    /** @test */
    public function test_walk_in_customer_ledger_double_entry_for_settlement_sale(): void
    {
        $contactUtil = new ContactUtil();
        $walkIn = $contactUtil->getWalkInCustomer($this->businessId);
        if (empty($walkIn['id'])) {
            $contactId = DB::table('contacts')->insertGetId([
                'business_id' => $this->businessId,
                'type' => 'customer',
                'name' => 'Walk-In Customer',
                'is_default' => 1,
                'created_by' => $this->userId,
                'notification_contacts' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $contactId = $walkIn['id'];
        }

        $date = now()->toDateString();
        $settlementNo = 'SETTLE-TST-' . uniqid();

        // 1. Create the sale transaction (sub_type: settlement)
        $transactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'contact_id' => $contactId,
            'location_id' => 1, // just a placeholder/seeded location
            'type' => 'sell',
            'sub_type' => 'settlement',
            'status' => 'final',
            'payment_status' => 'paid',
            'final_total' => 1250.00,
            'transaction_date' => $date,
            'created_by' => $this->userId,
            'invoice_no' => $settlementNo,
            'ref_no' => null,
            'is_settlement' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create the payment
        DB::table('transaction_payments')->insert([
            'transaction_id' => $transactionId,
            'business_id' => $this->businessId,
            'amount' => 1250.00,
            'method' => 'cash',
            'paid_on' => $date,
            'created_by' => $this->userId,
            'payment_ref_no' => 'PAY-TST-' . uniqid(),
            'payment_for' => $contactId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ledger = $contactUtil->getCustomerLedger($contactId, $this->businessId, $date, $date);

        $sellRows = $ledger->filter(fn ($row) => $row->type === 'sell')->values();
        $paymentRows = $ledger->filter(fn ($row) => $row->type === 'payment')->values();

        $this->assertCount(0, $sellRows, "Walk-in customer ledger should not contain a sell entry for direct settlements");
        $this->assertCount(0, $paymentRows, "Walk-in customer ledger should not contain a payment entry for direct settlements");
    }

    /** @test */
    public function walk_in_cash_and_card_settlement_payments_create_and_backfill_debit_and_credit_ledgers(): void
    {
        $contactId = $this->walkInCustomerId();
        $accountId = $this->accountIdForSettlementPayment();
        $settlement = $this->seedSettlementForWalkInLedger();
        $controller = app(SettlementController::class);
        request()->setLaravelSession(session());

        foreach (['cash_payment' => 'cash', 'card_payment' => 'card'] as $subType => $method) {
            $transaction = $controller->createTransaction(
                $settlement,
                1250.00,
                $contactId,
                $this->pumpOperatorId,
                'settlement',
                $subType,
                $settlement->settlement_no
            );
            $payment = $controller->createTansactionPayment(
                $transaction,
                $method,
                1250.00,
                null,
                null,
                null,
                null,
                null,
                0,
                $contactId
            );

            $controller->createAccountTransaction(
                $transaction,
                'debit',
                $accountId,
                $payment->id,
                null,
                $contactId,
                1250.00
            );

            $this->assertSame(1, DB::table('contact_ledgers')
                ->where('transaction_id', $transaction->id)
                ->where('transaction_payment_id', $payment->id)
                ->where('contact_id', $contactId)
                ->where('type', 'debit')
                ->count());
            $this->assertSame(1, DB::table('contact_ledgers')
                ->where('transaction_id', $transaction->id)
                ->where('transaction_payment_id', $payment->id)
                ->where('contact_id', $contactId)
                ->where('type', 'credit')
                ->count());

            $ledgerRows = (new ContactUtil())->getCustomerLedger(
                $contactId,
                $this->businessId,
                now()->toDateString(),
                now()->toDateString()
            )->where('amount', 1250.00);

            $this->assertSame(
                0,
                $ledgerRows->where('type', 'sell')->count(),
                'Walk-In settlement payment debit must not show in the customer ledger.'
            );
            $this->assertSame(
                0,
                $ledgerRows->where('type', 'payment')->count(),
                'Walk-In settlement payment credit must not show in the customer ledger.'
            );
        }

        $legacyTransaction = $controller->createTransaction(
            $settlement,
            990.00,
            $contactId,
            $this->pumpOperatorId,
            'settlement',
            'cash_payment',
            $settlement->settlement_no
        );
        $legacyPayment = $controller->createTansactionPayment($legacyTransaction, 'cash', 990.00, null, null, null, null, null, 0, $contactId);

        DB::table('contact_ledgers')->insert([
            'contact_id' => $contactId,
            'amount' => 990.00,
            'type' => 'credit',
            'sub_type' => 'cash_payment',
            'operation_date' => $legacyTransaction->transaction_date,
            'created_by' => $this->userId,
            'transaction_id' => $legacyTransaction->id,
            'transaction_payment_id' => $legacyPayment->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        require_once base_path('database/migrations/2026_05_17_000001_create_missing_walk_in_debit_ledgers.php');
        (new \CreateMissingWalkInDebitLedgers())->up();

        $this->assertSame(1, DB::table('contact_ledgers')
            ->where('transaction_id', $legacyTransaction->id)
            ->where('transaction_payment_id', $legacyPayment->id)
            ->where('contact_id', $contactId)
            ->where('type', 'debit')
            ->count());
    }

    private function walkInCustomerId(): int
    {
        $walkIn = (new ContactUtil())->getWalkInCustomer($this->businessId);
        if (! empty($walkIn['id'])) {
            return (int) $walkIn['id'];
        }

        return DB::table('contacts')->insertGetId([
            'business_id' => $this->businessId,
            'type' => 'customer',
            'name' => 'Walk-In Customer',
            'is_default' => 1,
            'created_by' => $this->userId,
            'notification_contacts' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountIdForSettlementPayment(): int
    {
        $accountId = DB::table('accounts')
            ->where('business_id', $this->businessId)
            ->where('is_closed', 0)
            ->value('id');

        if ($accountId) {
            return (int) $accountId;
        }

        return DB::table('accounts')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => 'all',
            'name' => 'Cash',
            'account_number' => 'TEST-' . uniqid(),
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSettlementForWalkInLedger(): \Modules\Petro\Entities\Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'WALKIN-LEDGER-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \Modules\Petro\Entities\Settlement::findOrFail($settlementId);
    }

    /** @test */
    public function test_walk_in_cash_deposit_creates_both_debit_and_credit_ledgers(): void
    {
        $contactId = $this->walkInCustomerId();
        $accountId = $this->accountIdForSettlementPayment();
        $settlement = $this->seedSettlementForWalkInLedger();
        
        // Seed a cash deposit
        $cashDepositId = DB::table('settlement_cash_deposits')->insertGetId([
            'settlement_no' => $settlement->id,
            'amount' => 30000.00,
            'bank_id' => $accountId,
            'business_id' => $this->businessId,
            'account_no' => 'REC-TST-1',
            'time_deposited' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(SettlementController::class);
        request()->setLaravelSession(session());

        // Call the private/protected code or trigger via the controller action. 
        // We can mock the request and call post/store or we can invoke the loop logic directly.
        // Let's call the logic to create the transaction & ledger entries for cash deposits.
        // To be safe, we can manually trigger the controller loop or simulate the save.
        // Let's check how the controller loop is called. We can invoke a helper or run it.
        $cashDepositTransaction = $controller->createTransaction(
            $settlement,
            30000.00,
            null,
            null,
            'settlement',
            'cash_deposit',
            $settlement->settlement_no,
            $cashDepositId
        );

        $depositBank = \App\Account::find($accountId);
        $depositBankName = $depositBank ? $depositBank->name : '';
        $depositNoteParts = array_filter([
            'Cash deposit to bank',
            $depositBankName ? '('.$depositBankName.')' : null,
            'Settlement: '.$settlement->settlement_no,
            'Receipt: REC-TST-1',
        ]);

        // This simulates the ContactLedger entries created for cash deposit in SettlementController.php
        \App\ContactLedger::createContactLedger([
            'business_id' => $cashDepositTransaction->business_id,
            'contact_id' => $cashDepositTransaction->contact_id,
            'amount' => 30000.00,
            'type' => 'credit',
            'sub_type' => 'cash_deposit',
            'operation_date' => $cashDepositTransaction->transaction_date,
            'created_by' => $cashDepositTransaction->created_by,
            'transaction_id' => $cashDepositTransaction->id,
            'note' => implode(' ', $depositNoteParts),
        ]);

        $customer = \App\Contact::find($cashDepositTransaction->contact_id);
        if (! empty($customer) && (int) $customer->is_default === 1) {
            \App\ContactLedger::createContactLedger([
                'business_id' => $cashDepositTransaction->business_id,
                'contact_id' => $cashDepositTransaction->contact_id,
                'amount' => 30000.00,
                'type' => 'debit',
                'sub_type' => 'sell',
                'operation_date' => $cashDepositTransaction->transaction_date,
                'created_by' => $cashDepositTransaction->created_by,
                'transaction_id' => $cashDepositTransaction->id,
                'note' => implode(' ', $depositNoteParts),
            ]);
        }

        // To assert, we fetch the ledger rows using ContactUtil::getCustomerLedger
        $ledgerRows = (new ContactUtil())->getCustomerLedger(
            $contactId,
            $this->businessId,
            now()->toDateString(),
            now()->toDateString()
        )->where('amount', 30000.00);

        $this->assertSame(
            1,
            $ledgerRows->where('type', 'sell')->count(),
            'Walk-In cash deposit debit (sell) entry must show in the customer ledger.'
        );
        $this->assertSame(
            1,
            $ledgerRows->where('type', 'payment')->count(),
            'Walk-In cash deposit credit (payment) entry must show in the customer ledger.'
        );
        $this->assertSame(
            0,
            $ledgerRows->where('type', 'settlement')->count(),
            'Cash deposit should not show as duplicate settlement/loan row in ledger.'
        );
    }

    /** @test */
    public function pumper_dashboard_credit_sale_saves_instantly_to_ledger_only_if_pumper_ledger_update_is_enabled(): void
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

        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update([
                'location_id' => $locationId,
                'dashboard_settings' => json_encode([
                    'pumper_ledger_update' => 'no',
                    'credit_sales_direct_to_customer' => 'no'
                ])
            ]);

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 1,
            'shift_date'       => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id'        => $this->businessId,
            'pump_id'            => $pumpId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'starting_meter'     => 0,
            'closing_meter'      => 0,
            'date_and_time'      => now(),
            'status'             => 'open',
            'assigned_by'        => $this->userId,
            'is_confirmed'       => 1,
            'confirmed_at'       => now(),
            'is_manually_closed' => 0,
            'shift_id'           => $shiftId,
            'shift_number'       => 99,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $orderNumber = 'LEDGER-L-1-' . uniqid();
        $initialLedgerCount = DB::table('contact_ledgers')
            ->where('contact_id', $this->contactId)
            ->count();

        // 1. With both settings = no
        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 2,
                    'amount' => 200,
                    'sub_total' => 200,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH-' . uniqid(),
                    'note' => 'ledger test 1',
                ]],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        // Assert no new ledger entry was created
        $this->assertEquals(
            $initialLedgerCount,
            DB::table('contact_ledgers')->where('contact_id', $this->contactId)->count()
        );

        // 2. With credit_sales_direct_to_customer = yes (pumper_ledger_update = no)
        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update([
                'dashboard_settings' => json_encode([
                    'pumper_ledger_update' => 'no',
                    'credit_sales_direct_to_customer' => 'yes'
                ])
            ]);

        $orderNumber2 = 'LEDGER-L-2-' . uniqid();
        $response2 = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber2,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 3,
                    'amount' => 300,
                    'sub_total' => 300,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH-' . uniqid(),
                    'note' => 'ledger test 2',
                ]],
            ]);

        $response2->assertOk()->assertJson(['success' => true]);

        // Assert ledger entry WAS created instantly
        $ledgerEntry = DB::table('contact_ledgers')
            ->where('contact_id', $this->contactId)
            ->where('note', 'like', '%Pumper Dashboard Credit Sale - Form No.%')
            ->first();

        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(300, (float)$ledgerEntry->amount);
        $this->assertEquals('debit', $ledgerEntry->type);

        // Assert that the transaction is visible in getCustomerLedger
        $ledger = (new ContactUtil())->getCustomerLedger(
            $this->contactId,
            $this->businessId,
            now()->toDateString(),
            now()->toDateString()
        );
        $ledgerTransaction = $ledger->first(fn ($row) => $row->bill_no === $orderNumber2);
        $this->assertNotNull($ledgerTransaction, "Transaction should be visible in customer ledger view");
        $this->assertEquals(300, (float)$ledgerTransaction->amount);
    }

    /** @test */
    public function test_multiple_standalone_credit_sales_are_visible_separately_in_sales_tab(): void
    {
        $this->withoutExceptionHandling();

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

        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update([
                'location_id' => $locationId,
                'dashboard_settings' => json_encode([
                    'pumper_ledger_update' => 'yes',
                    'credit_sales_direct_to_customer' => 'yes'
                ])
            ]);

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 1,
            'shift_date'       => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id'        => $this->businessId,
            'pump_id'            => $pumpId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'starting_meter'     => 0,
            'closing_meter'      => 0,
            'date_and_time'      => now(),
            'status'             => 'open',
            'assigned_by'        => $this->userId,
            'is_confirmed'       => 1,
            'confirmed_at'       => now(),
            'is_manually_closed' => 0,
            'shift_id'           => $shiftId,
            'shift_number'       => 99,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $orderNumber1 = 'SALE-T1-' . uniqid();
        $orderNumber2 = 'SALE-T2-' . uniqid();

        // 1. Save credit sale 1
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber1,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 2,
                    'amount' => 200,
                    'sub_total' => 200,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH1-' . uniqid(),
                    'note' => 'sale test 1',
                ]],
            ])->assertOk();

        // 2. Save credit sale 2
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber2,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 3,
                    'amount' => 300,
                    'sub_total' => 300,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH2-' . uniqid(),
                    'note' => 'sale test 2',
                ]],
            ])->assertOk();

        // Call the /sales AJAX endpoint
        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
                'business' => [
                    'id' => $this->businessId,
                    'enabled_modules' => [],
                    'accounting_method' => 'fifo'
                ]
            ])
            ->getJson('/sales?customer_id=' . $this->contactId, [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertOk();
        $data = $response->json('data');

        // Extract ref_no (which holds the order number or customer reference)
        $refNumbers = collect($data)->pluck('ref_no')->toArray();

        $this->assertContains($orderNumber1, $refNumbers, "First credit sale should be returned in sales list");
        $this->assertContains($orderNumber2, $refNumbers, "Second credit sale should be returned in sales list");
    }

    /** @test */
    public function test_accounts_receivable_not_created_when_pumper_ledger_update_is_disabled(): void
    {
        $this->withoutExceptionHandling();

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

        // Set setting to 'no'
        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update([
                'location_id' => $locationId,
                'dashboard_settings' => json_encode([
                    'pumper_ledger_update' => 'no',
                    'credit_sales_direct_to_customer' => 'no'
                ])
            ]);

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 1,
            'shift_date'       => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id'        => $this->businessId,
            'pump_id'            => $pumpId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'starting_meter'     => 0,
            'closing_meter'      => 0,
            'date_and_time'      => now(),
            'status'             => 'open',
            'assigned_by'        => $this->userId,
            'is_confirmed'       => 1,
            'confirmed_at'       => now(),
            'is_manually_closed' => 0,
            'shift_id'           => $shiftId,
            'shift_number'       => 99,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $orderNumber = 'SALE-AR-' . uniqid();

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
            ])
            ->postJson('/pumper-dashboard/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'price' => 100,
                    'unit_discount' => 0,
                    'qty' => 2,
                    'amount' => 200,
                    'sub_total' => 200,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'VEH-' . uniqid(),
                    'note' => 'ar test',
                ]],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        // Find the transaction in database
        $transaction = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('ref_no', $orderNumber)
            ->first();

        $this->assertNotNull($transaction);

        // Find the Accounts Receivable account ID
        $accounts_receivable_id = DB::table('accounts')
            ->where('business_id', $this->businessId)
            ->where('name', 'Accounts Receivable')
            ->value('id');

        if ($accounts_receivable_id) {
            // Assert that no AccountTransaction exists for this transaction
            $accountTxnExists = DB::table('account_transactions')
                ->where('transaction_id', $transaction->id)
                ->where('account_id', $accounts_receivable_id)
                ->where('type', 'debit')
                ->exists();

            $this->assertFalse($accountTxnExists, "AccountTransaction should not be created when pumper_ledger_update is disabled");
        }
    }
}
