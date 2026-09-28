<?php

namespace Tests\Feature\Petro;

use App\User;
use App\Utils\TransactionUtil;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Http\Controllers\PumpOperatorController;
use ReflectionMethod;

class PumpOperatorLedgerRecoveryPaymentTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pump_operator_ledger_shows_shortage_recoveries_and_excess_payments_added_from_actions(): void
    {
        $date = now()->toDateString();
        $this->actingAs(User::find($this->userId));
        DB::table('users')->where('id', $this->userId)->update(['business_id' => $this->businessId]);
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);

        $cashAccountId = $this->ensureLedgerAccount('Cash');
        $this->ensureLedgerAccount('Accounts Receivable');

        $this->seedPumpOperatorLedgerDue('shortage', 300, $date);
        $this->seedPumpOperatorLedgerDue('excess', 200, $date);

        $inputs = [
            'amount' => 100,
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
            'payment_ref_no' => 'PUMP-LEDGER-' . uniqid(),
            'paid_on' => $date,
        ];

        app(TransactionUtil::class)->payAtOnceExcessShortage($inputs, 'shortage', $this->pumpOperatorId);
        app(TransactionUtil::class)->payAtOnceExcessShortage(
            array_merge($inputs, ['amount' => 50, 'payment_ref_no' => 'PUMP-EXCESS-' . uniqid()]),
            'excess',
            $this->pumpOperatorId
        );

        $method = new ReflectionMethod(PumpOperatorController::class, 'getLedgerTransactionsCollection');
        $method->setAccessible(true);
        $ledgerRows = $method->invoke(app(PumpOperatorController::class), $this->pumpOperatorId, $date, $date);

        $this->assertTrue(
            $ledgerRows->contains(fn ($row) => $row->type === 'shortage_recovered' && (float) $row->amount === 100.0),
            'Shortage recovery payment should appear in pump operator ledger rows.'
        );
        $this->assertTrue(
            $ledgerRows->contains(fn ($row) => $row->type === 'excess_paid' && (float) $row->amount === 50.0),
            'Excess payment should appear in pump operator ledger rows.'
        );
    }

    /** @test */
    public function pump_operator_ledger_summary_total_excess_excludes_shortage_recovery_payments(): void
    {
        $date = now()->toDateString();
        $this->actingAs(User::find($this->userId));
        DB::table('users')->where('id', $this->userId)->update(['business_id' => $this->businessId]);
        request()->setLaravelSession($this->app['session.store']);
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);

        $cashAccountId = $this->ensureLedgerAccount('Cash');
        $this->ensureLedgerAccount('Accounts Receivable');

        $this->seedPumpOperatorLedgerDueWithLedgerEntry('shortage', 300, $date);
        $this->seedPumpOperatorLedgerDueWithLedgerEntry('excess', 200, $date);

        app(TransactionUtil::class)->payAtOnceExcessShortage([
            'amount' => 100,
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
            'payment_ref_no' => 'PUMP-SHORT-REC-' . uniqid(),
            'paid_on' => $date,
        ], 'shortage', $this->pumpOperatorId);

        $summary = app(TransactionUtil::class)->getPumpOperatorLedgerSummary(
            $this->businessId,
            $date,
            $date,
            $this->pumpOperatorId
        );

        $this->assertSame(200.0, (float) $summary['total_credit_for_period']);
    }

    private function seedPumpOperatorLedgerDueWithLedgerEntry(string $subType, float $amount, string $date): int
    {
        $transactionId = $this->seedPumpOperatorLedgerDue($subType, $amount, $date);

        DB::table('account_transactions')->insert([
            'account_id' => $this->ensureLedgerAccount('Accounts Receivable'),
            'business_id' => $this->businessId,
            'type' => $subType === 'shortage' ? 'debit' : 'credit',
            'sub_type' => 'ledger_show',
            'amount' => $amount,
            'operation_date' => $date,
            'created_by' => $this->userId,
            'transaction_id' => $transactionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $transactionId;
    }

    private function seedPumpOperatorLedgerDue(string $subType, float $amount, string $date): int
    {
        return DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => DB::table('pump_operators')->where('id', $this->pumpOperatorId)->value('location_id'),
            'type' => 'settlement',
            'sub_type' => $subType,
            'status' => 'final',
            'payment_status' => 'due',
            'contact_id' => $this->contactId,
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => $date,
            'total_before_tax' => $amount,
            'final_total' => $amount,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureLedgerAccount(string $name): int
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
}
