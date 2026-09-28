<?php

namespace Tests\Feature;

use App\Account;
use App\Business;
use App\BusinessLocation;
use App\ExpenseCategory;
use App\Transaction;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiExpenseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_multi_expense_categories_are_shown_in_edit_form(): void
    {
        // 1. Setup authenticated user and business context
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // Ensure we are authenticated
        $this->actingAs($user);

        // Set session variables
        session([
            'business' => $business,
            'user' => $user,
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ]);

        // 2. Create categories and accounts
        $cat1 = ExpenseCategory::create([
            'name' => 'Salaries and wages',
            'business_id' => $business->id,
        ]);

        $cat2 = ExpenseCategory::create([
            'name' => 'Meals & Tea',
            'business_id' => $business->id,
        ]);

        $account = Account::create([
            'name' => 'Test Expense Account',
            'business_id' => $business->id,
            'account_type_id' => null,
        ]);

        // 3. Post a multi-expense creation request
        $postData = [
            'location_id' => $location->id,
            'transaction_date' => '31-05-2026',
            'expense_items' => [
                [
                    'expense_category_id' => $cat1->id,
                    'expense_account' => $account->id,
                    'amount' => '10.00',
                    'is_vat' => '0',
                    'tax_id' => '',
                    'ref_no' => 'EP2026/0002',
                    'additional_notes' => 'Salaries note',
                ],
                [
                    'expense_category_id' => $cat2->id,
                    'expense_account' => $account->id,
                    'amount' => '10.00',
                    'is_vat' => '0',
                    'tax_id' => '',
                    'ref_no' => 'EP2026/0002',
                    'additional_notes' => 'Meals note',
                ]
            ],
            'payment' => [
                [
                    'method' => 'credit_expense',
                    'amount' => '20.00',
                ]
            ],
            'final_total' => '20.00',
        ];

        $response = $this->post('/expenses', $postData);
        $response->assertRedirect('/expenses');

        // 4. Verify transactions are created
        $transactions = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->orderBy('id', 'asc')
            ->get();

        // There should be 2 transactions
        $this->assertCount(2, $transactions);

        $parentTx = $transactions->first();
        $childTx = $transactions->last();

        // Asserts relation
        $this->assertNull($parentTx->parent_transaction_id);
        $this->assertEquals($parentTx->id, $childTx->parent_transaction_id);

        // 5. Test edit page loads both expense items
        $editResponse = $this->get('/expenses/' . $parentTx->id . '/edit');
        $editResponse->assertStatus(200);

        // Verify the HTML includes the details of both items
        $editResponse->assertSee($cat1->name);
        $editResponse->assertSee($cat2->name);

        // 6. Test updating the multi-expense
        $updateData = [
            'location_id' => $location->id,
            'transaction_date' => '31-05-2026',
            'expense_items' => [
                [
                    'expense_category_id' => $cat1->id,
                    'expense_account' => $account->id,
                    'amount' => '15.00',
                    'is_vat' => '0',
                    'tax_id' => '',
                    'ref_no' => 'EP2026/0002',
                    'additional_notes' => 'Salaries updated note',
                ],
                [
                    'expense_category_id' => $cat2->id,
                    'expense_account' => $account->id,
                    'amount' => '25.00',
                    'is_vat' => '0',
                    'tax_id' => '',
                    'ref_no' => 'EP2026/0002',
                    'additional_notes' => 'Meals updated note',
                ]
            ],
            'payment' => [
                [
                    'method' => 'credit_expense',
                    'amount' => '40.00',
                ]
            ],
            'final_total' => '40.00',
        ];

        $updateResponse = $this->put('/expenses/' . $parentTx->id, $updateData);
        $updateResponse->assertRedirect('/expenses');

        $ajaxResponse = $this->get('/expenses', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $ajaxResponse->assertStatus(200);
        $ajaxData = $ajaxResponse->json();
        $this->assertCount(2, $ajaxData['data']);

        // Check database after update
        $updatedTransactions = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(2, $updatedTransactions);
        $newParent = $updatedTransactions->first();
        $newChild = $updatedTransactions->last();

        $this->assertEquals(15.00, $newParent->final_total);
        $this->assertEquals(25.00, $newChild->final_total);
        $this->assertEquals($newParent->id, $newChild->parent_transaction_id);

        // 7. Test deleting the multi-expense
        $deleteResponse = $this->delete('/expenses/' . $newParent->id, [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);
        $deleteResponse->assertJson(['success' => true]);

        // Verify both transactions are marked as deleted (deleted_by is not null)
        $deletedCount = Transaction::where('business_id', $business->id)
            ->where('type', 'expense')
            ->whereNull('deleted_by')
            ->count();
        $this->assertEquals(0, $deletedCount);
    }
}
