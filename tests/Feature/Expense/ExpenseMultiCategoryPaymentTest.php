<?php

namespace Tests\Feature\Expense;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\ExpenseCategory;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpenseMultiCategoryPaymentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_multi_category_expense_creation_with_full_payment_sets_status_to_paid(): void
    {
        $business = Business::first() ?: Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'start_date' => '2026-01-01',
            'time_zone' => 'Asia/Jakarta'
        ]);

        $user = User::where('business_id', $business->id)->first() ?: User::create([
            'surname' => 'Mr',
            'first_name' => 'Admin',
            'email' => 'admin@test.com',
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'business_id' => $business->id
        ]);

        $this->actingAs($user);

        $expenseCategory1 = ExpenseCategory::create([
            'name' => 'Test Category 1',
            'business_id' => $business->id
        ]);

        $expenseCategory2 = ExpenseCategory::create([
            'name' => 'Test Category 2',
            'business_id' => $business->id
        ]);

        $expenseAccount = Account::create([
            'name' => 'Test Expense Account',
            'business_id' => $business->id,
            'account_number' => 'EXP-ACC-002',
            'created_by' => $user->id
        ]);

        $cashAccount = Account::create([
            'name' => 'Cash Account',
            'business_id' => $business->id,
            'account_number' => 'CSH-002',
            'created_by' => $user->id
        ]);

        // Post expense request with multiple categories, fully paid
        $response = $this->post('/expenses', [
            'transaction_date' => '23-05-2026',
            'final_total' => '300.00',
            'expense_account' => $expenseAccount->id,
            'expense_items' => [
                [
                    'expense_category_id' => $expenseCategory1->id,
                    'amount' => '100.00',
                    'expense_account' => $expenseAccount->id,
                    'is_vat' => 0,
                    'additional_notes' => 'notes 1'
                ],
                [
                    'expense_category_id' => $expenseCategory2->id,
                    'amount' => '200.00',
                    'expense_account' => $expenseAccount->id,
                    'is_vat' => 0,
                    'additional_notes' => 'notes 2'
                ]
            ],
            'payment' => [
                [
                    'method' => 'cash',
                    'amount' => '300.00',
                    'account_id' => $cashAccount->id
                ]
            ]
        ]);

        $response->assertStatus(302);

        // Fetch the created transactions
        $transactions = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->whereIn('expense_category_id', [$expenseCategory1->id, $expenseCategory2->id])
            ->get();

        $this->assertCount(2, $transactions);

        foreach ($transactions as $tx) {
            // Assert each individual transaction's payment status is paid (not due!)
            $this->assertEquals('paid', $tx->payment_status);

            // Assert that transaction payments are created for each transaction with the correct amount
            $payments = TransactionPayment::where('transaction_id', $tx->id)->get();
            $this->assertCount(1, $payments);
            $this->assertEquals($tx->final_total, $payments->first()->amount);
        }
    }

    public function test_editing_expense_to_credit_expense_deletes_payment_and_sets_status_to_due(): void
    {
        $business = Business::first() ?: Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'start_date' => '2026-01-01',
            'time_zone' => 'Asia/Jakarta'
        ]);

        $user = User::where('business_id', $business->id)->first() ?: User::create([
            'surname' => 'Mr',
            'first_name' => 'Admin',
            'email' => 'admin@test.com',
            'username' => 'admin_test',
            'password' => bcrypt('password'),
            'business_id' => $business->id
        ]);

        $this->actingAs($user);

        $expenseCategory = ExpenseCategory::create([
            'name' => 'Test Category',
            'business_id' => $business->id
        ]);

        $expenseAccount = Account::create([
            'name' => 'Test Expense Account',
            'business_id' => $business->id,
            'account_number' => 'EXP-ACC-003',
            'created_by' => $user->id
        ]);

        $cashAccount = Account::create([
            'name' => 'Cash Account 2',
            'business_id' => $business->id,
            'account_number' => 'CSH-003',
            'created_by' => $user->id
        ]);

        // 1. Create a fully paid expense transaction
        $transaction = Transaction::create([
            'business_id' => $business->id,
            'location_id' => 1,
            'type' => 'expense',
            'status' => 'final',
            'payment_status' => 'paid',
            'ref_no' => 'EXP-9999',
            'transaction_date' => '2026-05-30 00:00:00',
            'final_total' => 150.00,
            'total_before_tax' => 150.00,
            'expense_category_id' => $expenseCategory->id,
            'expense_account' => $expenseAccount->id,
            'created_by' => $user->id
        ]);

        $tp = TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'business_id' => $business->id,
            'amount' => 150.00,
            'method' => 'cash',
            'account_id' => $cashAccount->id,
            'paid_on' => '2026-05-30 00:00:00',
            'created_by' => $user->id
        ]);

        // Verify pre-conditions
        $this->assertEquals('paid', $transaction->payment_status);
        $this->assertDatabaseHas('transaction_payments', ['id' => $tp->id]);

        // 2. Edit the expense to credit_expense
        $response = $this->put('/expenses/' . $transaction->id, [
            'transaction_date' => '30-05-2026',
            'final_total' => '150.00',
            'expense_account' => $expenseAccount->id,
            'expense_category_id' => $expenseCategory->id,
            'payment' => [
                [
                    'method' => 'credit_expense',
                    'amount' => '0.00',
                    'account_id' => ''
                ]
            ]
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status', ['success' => 1, 'msg' => __('expense.expense_update_success')]);

        // 3. Assertions
        $transaction->refresh();
        $this->assertEquals('due', $transaction->payment_status);
        $this->assertSoftDeleted('transaction_payments', ['id' => $tp->id]);
    }
}

