<?php

namespace Tests\Feature\Petro;

use App\Http\Controllers\ReportController;
use App\User;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class ManagementReportPumpOperatorShortageExcessTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function balance_to_operator_shortage_is_today_shortage_not_recovered_or_excess_paid(): void
    {
        $date = Carbon::today()->subDay()->toDateString();
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'BTO Shortage Report Test ' . uniqid(),
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transactionId = DB::table('transactions')->insertGetId($this->transactionRow([
            'location_id' => $locationId,
            'type' => 'settlement',
            'sub_type' => 'shortage',
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => $date,
            'final_total' => 2000,
            'total_before_tax' => 2000,
            'transaction_note' => null,
        ]));

        DB::table('account_transactions')->insert([
            'business_id' => $this->businessId,
            'account_id' => DB::table('accounts')->where('business_id', $this->businessId)->value('id') ?: 1,
            'type' => 'credit',
            'sub_type' => 'ledger_show',
            'amount' => 2000,
            'operation_date' => $date,
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(ReportController::class);

        $shortage = $controller->getPreviousDayPumpOperatorShortage($date, $date, $locationId);
        $excess = $controller->getPreviousDayPumpOperatorExcess($date, $date, $locationId);

        $this->assertEqualsWithDelta(2000, $shortage['given'], 0.001);
        $this->assertEqualsWithDelta(0, $shortage['received'], 0.001);
        $this->assertEqualsWithDelta(0, $excess['given'], 0.001);
        $this->assertEqualsWithDelta(0, $excess['received'], 0.001);
    }

    /** @test */
    public function recovered_shortage_keeps_today_shortage_as_remaining_due_and_reports_recovered_amount(): void
    {
        $date = Carbon::today()->subDay()->toDateString();
        $this->actingAs(User::find($this->userId));
        DB::table('users')->where('id', $this->userId)->update(['business_id' => $this->businessId]);
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Recovered Shortage Report Test ' . uniqid(),
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pump_operators')->where('id', $this->pumpOperatorId)->update(['location_id' => $locationId]);
        $cashAccountId = $this->ensureAccount('Cash');
        $this->ensureAccount('Accounts Receivable');

        $transactionId = DB::table('transactions')->insertGetId($this->transactionRow([
            'location_id' => $locationId,
            'type' => 'settlement',
            'sub_type' => 'shortage',
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => $date,
            'final_total' => 3275,
            'total_before_tax' => 3275,
            'transaction_note' => null,
        ]));

        DB::table('account_transactions')->insert([
            'business_id' => $this->businessId,
            'account_id' => $cashAccountId,
            'type' => 'debit',
            'sub_type' => 'ledger_show',
            'amount' => 3275,
            'operation_date' => $date,
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TransactionUtil::class)->payAtOnceExcessShortage([
            'amount' => 300,
            'method' => 'cash',
            'account_id' => $cashAccountId,
            'note' => null,
            'card_number' => null,
            'card_holder_name' => null,
            'card_transaction_number' => null,
            'card_type' => null,
            'card_month' => null,
            'card_year' => null,
            'card_security' => null,
            'cheque_number' => null,
            'bank_account_number' => null,
            'payment_ref_no' => 'SHORTAGE-RECOVER-' . uniqid(),
            'paid_on' => $date,
        ], 'shortage', $this->pumpOperatorId);

        $shortage = app(ReportController::class)->getPreviousDayPumpOperatorShortage($date, $date, $locationId);

        $this->assertEqualsWithDelta(3275, $shortage['given'], 0.001);
        $this->assertEquals(1, DB::table('transactions')->where('type', 'shortage_bulk_payment')->where('location_id', $locationId)->count());
        $this->assertEquals(1, DB::table('account_transactions')
            ->join('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where('transactions.type', 'shortage_bulk_payment')
            ->where('transactions.location_id', $locationId)
            ->where('account_transactions.type', 'credit')
            ->where('account_transactions.sub_type', 'ledger_show')
            ->count());
        $this->assertEqualsWithDelta(300, $shortage['received'], 0.001);
        $this->assertEqualsWithDelta(2975, $shortage['balance'], 0.001);
    }

    /** @test */
    public function balance_to_operator_excess_is_today_excess_not_today_shortage(): void
    {
        $date = now()->toDateString();
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'BTO Excess Report Test ' . uniqid(),
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $transactionId = DB::table('transactions')->insertGetId($this->transactionRow([
            'location_id' => $locationId,
            'type' => 'settlement',
            'sub_type' => 'excess',
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => $date,
            'final_total' => 2000,
            'total_before_tax' => 2000,
            'transaction_note' => null,
        ]));

        DB::table('account_transactions')->insert([
            'business_id' => $this->businessId,
            'account_id' => DB::table('accounts')->where('business_id', $this->businessId)->value('id') ?: 1,
            'type' => 'debit',
            'sub_type' => 'ledger_show',
            'amount' => 2000,
            'operation_date' => $date,
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = app(ReportController::class);

        $shortage = $controller->getPreviousDayPumpOperatorShortage($date, $date, $locationId);
        $excess = $controller->getPreviousDayPumpOperatorExcess($date, $date, $locationId);

        $this->assertEqualsWithDelta(0, $shortage['given'], 0.001);
        $this->assertEqualsWithDelta(0, $shortage['received'], 0.001);
        $this->assertEqualsWithDelta(2000, $excess['given'], 0.001);
        $this->assertEqualsWithDelta(0, $excess['received'], 0.001);
    }

    /** @test */
    public function shortage_and_excess_balances_use_their_own_previous_day_balance_plus_today_less_paid(): void
    {
        $date = Carbon::today()->toDateString();
        $previousDate = Carbon::today()->subDay()->toDateString();
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);

        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $this->businessId,
            'name' => 'Pump Operator Balance Formula Test ' . uniqid(),
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = $this->ensureAccount('Cash');

        $this->seedPumpOperatorLedgerTransaction($previousDate, $locationId, 'settlement', 'shortage', 'debit', 1000, $accountId);
        $this->seedPumpOperatorLedgerTransaction($previousDate, $locationId, 'settlement', 'excess', 'credit', 300, $accountId);
        $this->seedPumpOperatorLedgerTransaction($date, $locationId, 'settlement', 'shortage', 'debit', 200, $accountId);
        $this->seedPumpOperatorLedgerTransaction($date, $locationId, 'shortage_bulk_payment', 'shortage', 'credit', 50, $accountId);
        $this->seedPumpOperatorLedgerTransaction($date, $locationId, 'settlement', 'excess', 'credit', 80, $accountId);
        $this->seedPumpOperatorLedgerTransaction($date, $locationId, 'excess_bulk_payment', 'excess', 'debit', 30, $accountId);

        $controller = app(ReportController::class);

        $shortage = $controller->getPreviousDayPumpOperatorShortage($date, $date, $locationId);
        $excess = $controller->getPreviousDayPumpOperatorExcess($date, $date, $locationId);

        $this->assertEqualsWithDelta(1000, $shortage['previous_day'], 0.001);
        $this->assertEqualsWithDelta(1150, $shortage['balance'], 0.001);
        $this->assertEqualsWithDelta(300, $excess['previous_day'], 0.001);
        $this->assertEqualsWithDelta(350, $excess['balance'], 0.001);
    }

    private function transactionRow(array $overrides = []): array
    {
        return array_merge([
            'business_id' => $this->businessId,
            'location_id' => DB::table('business_locations')->where('business_id', $this->businessId)->value('id'),
            'type' => 'settlement',
            'sub_type' => null,
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $this->contactId,
            'pump_operator_id' => null,
            'transaction_date' => now()->toDateString(),
            'total_before_tax' => 0,
            'final_total' => 0,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }

    private function ensureAccount(string $name): int
    {
        $accountId = DB::table('accounts')
            ->where('business_id', $this->businessId)
            ->where('name', $name)
            ->value('id');

        if ($accountId) {
            return (int) $accountId;
        }

        return DB::table('accounts')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => 'all',
            'name' => $name,
            'account_number' => 'TST-' . uniqid(),
            'account_type_id' => null,
            'created_by' => $this->userId,
            'is_main_account' => 0,
            'is_closed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPumpOperatorLedgerTransaction(
        string $date,
        int $locationId,
        string $transactionType,
        string $subType,
        string $accountTransactionType,
        float $amount,
        int $accountId
    ): int {
        $transactionId = DB::table('transactions')->insertGetId($this->transactionRow([
            'location_id' => $locationId,
            'type' => $transactionType,
            'sub_type' => $subType,
            'status' => 'final',
            'payment_status' => $transactionType === 'settlement' ? 'due' : 'paid',
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => $date,
            'final_total' => $amount,
            'total_before_tax' => $amount,
            'transaction_note' => null,
        ]));

        DB::table('account_transactions')->insert([
            'business_id' => $this->businessId,
            'account_id' => $accountId,
            'type' => $accountTransactionType,
            'sub_type' => 'ledger_show',
            'amount' => $amount,
            'operation_date' => $date,
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $transactionId;
    }
}
