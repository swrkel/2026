<?php

namespace Tests\Feature;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\ExpenseCategory;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpenseChequePaymentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_expense_creation_with_select_cheque_payment(): void
    {
        $business = Business::first();
        if (!$business) {
            $business = Business::create([
                'name' => 'Test Business',
                'currency_id' => 1,
                'start_date' => '2026-01-01',
                'time_zone' => 'Asia/Jakarta'
            ]);
        }

        $user = User::where('business_id', $business->id)->first();
        if (!$user) {
            $user = User::create([
                'surname' => 'Mr',
                'first_name' => 'Admin',
                'email' => 'admin@test.com',
                'username' => 'admin_test',
                'password' => bcrypt('password'),
                'business_id' => $business->id
            ]);
        }

        $this->actingAs($user);

        // Ensure "Cheques in Hand" account exists
        $chequeAccount = Account::where('business_id', $business->id)
            ->where('name', 'Cheques in Hand')
            ->first();
        if (!$chequeAccount) {
            $chequeAccount = Account::create([
                'name' => 'Cheques in Hand',
                'business_id' => $business->id,
                'account_number' => 'CHQ-001',
                'created_by' => $user->id
            ]);
        }

        // Create an expense category and expense account
        $expenseCategory = ExpenseCategory::create([
            'name' => 'Test Category',
            'business_id' => $business->id
        ]);

        $expenseAccount = Account::create([
            'name' => 'Test Expense Account',
            'business_id' => $business->id,
            'account_number' => 'EXP-ACC-001',
            'created_by' => $user->id
        ]);

        // Create a customer cheque (pending cheque in Cheques in Hand)
        $customerTx = Transaction::create([
            'business_id' => $business->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'final_total' => 1000,
            'transaction_date' => '2026-05-23 08:00:00',
            'created_by' => $user->id
        ]);

        $customerPayment = TransactionPayment::create([
            'transaction_id' => $customerTx->id,
            'business_id' => $business->id,
            'amount' => 1000,
            'method' => 'cheque',
            'cheque_number' => '123456',
            'cheque_date' => '2026-05-23',
            'account_id' => $chequeAccount->id,
            'is_deposited' => 0
        ]);

        $chequeAccountTx = AccountTransaction::create([
            'account_id' => $chequeAccount->id,
            'transaction_id' => $customerTx->id,
            'transaction_payment_id' => $customerPayment->id,
            'amount' => 1000,
            'type' => 'debit',
            'operation_date' => '2026-05-23 08:00:00',
            'created_by' => $user->id
        ]);

        // Post expense request paying via the customer cheque
        $response = $this->post('/expenses', [
            'transaction_date' => '23-05-2026',
            'final_total' => '1,000.00',
            'expense_account' => $expenseAccount->id,
            'expense_category_id' => $expenseCategory->id,
            'select_cheques' => [
                $chequeAccountTx->id
            ],
            'payment' => [
                [
                    'method' => 'cheque',
                    'amount' => '1,000.00',
                    'account_id' => $chequeAccount->id
                ]
            ]
        ]);

        $response->assertStatus(302);

        $expenseTx = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($expenseTx);

        // Assert that the credit transaction is created for Cheques in Hand (Account ID)
        $creditTransactions = AccountTransaction::where('transaction_id', $expenseTx->id)
            ->where('account_id', $chequeAccount->id)
            ->where('type', 'credit')
            ->get();

        $this->assertCount(1, $creditTransactions);
        $this->assertEquals(1000, $creditTransactions->first()->amount);

        // Assert that the transaction appears in the Cheques in Hand account book
        $ajaxResponse = $this->get("/accounting-module/account/{$chequeAccount->id}?start_date=2026-05-23&end_date=2026-05-23", [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);
        $ajaxResponse->assertStatus(200);
        $data = $ajaxResponse->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_manual_cheque_payment_creates_credit_transaction(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        $chequeAccount = Account::where('business_id', $business->id)
            ->where('name', 'Cheques in Hand')
            ->first();

        $expenseCategory = ExpenseCategory::first();
        $expenseAccount = Account::where('business_id', $business->id)
            ->where('name', '!=', 'Cheques in Hand')
            ->first();

        // Post expense request paying manually with cheque (without select_cheques)
        $response = $this->post('/expenses', [
            'transaction_date' => '23-05-2026',
            'final_total' => '1,000.00',
            'expense_account' => $expenseAccount->id,
            'expense_category_id' => $expenseCategory->id,
            'payment' => [
                [
                    'method' => 'cheque',
                    'amount' => '1,000.00',
                    'account_id' => $chequeAccount->id
                ]
            ]
        ]);

        $response->assertStatus(302);

        $expenseTx = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($expenseTx);

        // Assert debit transaction on expense account has amount 1000
        $debitTransactions = AccountTransaction::where('transaction_id', $expenseTx->id)
            ->where('account_id', $expenseAccount->id)
            ->where('type', 'debit')
            ->get();
        $this->assertCount(1, $debitTransactions);
        $this->assertEquals(1000, $debitTransactions->first()->amount);

        // Assert credit transaction on Cheques in Hand has amount 1000
        $creditTransactions = AccountTransaction::where('transaction_id', $expenseTx->id)
            ->where('account_id', $chequeAccount->id)
            ->where('type', 'credit')
            ->get();

        $this->assertCount(1, $creditTransactions);
        $this->assertEquals(1000, $creditTransactions->first()->amount);
    }

    public function test_expense_creation_with_credit_payment_status_is_due(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        $expenseCategory = ExpenseCategory::first();
        $expenseAccount = Account::where('business_id', $business->id)
            ->where('name', '!=', 'Cheques in Hand')
            ->first();

        // Post expense request with method "credit" (case-insensitive check)
        $response = $this->post('/expenses', [
            'transaction_date' => '23-05-2026',
            'final_total' => '500.00',
            'expense_account' => $expenseAccount->id,
            'expense_category_id' => $expenseCategory->id,
            'payment' => [
                [
                    'method' => 'credit',
                    'amount' => '500.00',
                    'account_id' => null
                ]
            ]
        ]);

        $response->assertStatus(302);

        $expenseTx = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($expenseTx);
        // Assert payment status is due
        $this->assertEquals('due', $expenseTx->payment_status);
    }
}
