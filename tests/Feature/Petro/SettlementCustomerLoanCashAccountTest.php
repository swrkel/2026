<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\SettlementController;

class SettlementCustomerLoanCashAccountTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function customer_loan_cash_account_has_only_one_debit_and_no_cash_credit_after_settlement_accounting_runs_twice(): void
    {
        request()->setLaravelSession($this->requestSession());

        $settlement = Settlement::findOrFail($this->seedFinalizedSettlement());
        $amount = 12500.00;
        $cashAccountId = $this->seedAccount('Cash');
        $this->seedAccount('Accounts Receivable');

        $customerLoanId = DB::table('settlement_customer_loans')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlement->id,
            'customer_id' => $this->contactId,
            'amount' => $amount,
            'note' => 'Loan to customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerLoan = \Modules\Petro\Entities\SettlementCustomerLoan::findOrFail($customerLoanId);
        $controller = app(SettlementController::class);
        $createTransaction = new \ReflectionMethod($controller, 'createTransaction');
        $createTransaction->setAccessible(true);
        $createAccountTransaction = new \ReflectionMethod($controller, 'createAccountTransaction');
        $createAccountTransaction->setAccessible(true);

        for ($i = 0; $i < 2; $i++) {
            $transaction = $createTransaction->invoke(
                $controller,
                $settlement,
                $customerLoan->amount,
                $customerLoan->customer_id,
                null,
                'settlement',
                'customer_loan',
                $settlement->settlement_no,
                null,
                0,
                0.0,
                $customerLoan->note
            );

            $createAccountTransaction->invoke(
                $controller,
                $transaction,
                'credit',
                $cashAccountId,
                $transaction->id,
                'null',
                null,
                $customerLoan->amount,
                false,
                $customerLoan->note
            );
        }

        $this->assertSame(
            1,
            DB::table('account_transactions')
                ->where('account_id', $cashAccountId)
                ->where('type', 'credit')
                ->where('amount', $amount)
                ->whereDate('operation_date', $settlement->transaction_date)
                ->where('note', 'Loan to customer')
                ->count(),
            'Saving the same settlement customer loan twice must leave one cash-account credit row.'
        );

        $this->assertSame(
            0,
            DB::table('account_transactions')
                ->where('account_id', $cashAccountId)
                ->where('type', 'debit')
                ->where('amount', $amount)
                ->whereDate('operation_date', $settlement->transaction_date)
                ->where('note', 'Loan to customer')
                ->count(),
            'Loan to customer must not also create a cash-account debit row.'
        );
    }

    private function seedFinalizedSettlement(): int
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        return DB::table('settlements')->insertGetId([
            'settlement_no' => 'ST-CUST-LOAN-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => '0',
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function requestSession(): Store
    {
        $session = app('session')->driver('array');
        $session->put('business.id', $this->businessId);
        $session->put('user.business_id', $this->businessId);
        $session->put('user.id', $this->userId);

        return $session;
    }

    private function seedAccount(string $name): int
    {
        $existing = DB::table('accounts')
            ->where('business_id', $this->businessId)
            ->where('name', $name)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return DB::table('accounts')->insertGetId([
            'name' => $name,
            'business_id' => $this->businessId,
            'created_by' => $this->userId,
            'location_id' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
