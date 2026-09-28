<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Transaction;
use App\TransactionPayment;

class ExpenseCategoryNoteTest extends TestCase
{
    public function test_expense_create_view_contains_expense_note_table_header_and_input(): void
    {
        $user = new User();
        $user->id = 1;
        $user->username = 'test_user';
        $this->actingAs($user);

        $data = [
            'cpc_accounts' => collect([]),
            'dailyCashShiftNumbers' => [],
            'bank_group_accounts' => collect([]),
            'cash_account_id' => 1,
            'ref_no' => 'EXP-001',
            'account_module' => true,
            'accounts' => [],
            'expense_accounts' => collect([]),
            'payment_types' => [],
            'payment_line' => [
                'method' => '',
                'amount' => 0,
                'note' => '',
                'card_transaction_number' => '',
                'card_number' => '',
                'card_type' => '',
                'card_holder_name' => '',
                'card_month' => '',
                'card_year' => '',
                'card_security' => '',
                'cheque_number' => '',
                'cheque_date' => '',
                'bank_account_number' => '',
                'is_return' => 0,
                'transaction_no' => ''
            ],
            'expense_categories' => collect([]),
            'business_locations' => collect([]),
            'users' => [],
            'employees' => collect([]),
            'fleets' => collect([]),
            'fleet_module' => false,
            'taxes' => [
                'tax_rates' => [],
                'attributes' => []
            ],
            'temp_data' => [],
            'contacts' => [],
            'current_liabilities_accounts' => collect([]),
            'expense_account_id' => null,
            'payee_name' => (object)['name' => 'Test Payee']
        ];

        request()->setLaravelSession($this->app['session.store']);

        $html = view('expense.create', $data)->render();

        // 1. Assert that the table header contains the translated "Expense note" (or the translation key)
        $this->assertStringContainsString(__('expense.expense_note'), $html);

        // 2. Assert that the JavaScript template contains the input with name expense_items[${expenseItemIndex}][additional_notes]
        $this->assertStringContainsString('name="expense_items[${expenseItemIndex}][additional_notes]"', $html);
    }

    public function test_expense_edit_view_contains_expense_category_table(): void
    {
        $user = new User();
        $user->id = 1;
        $user->username = 'test_user';
        $this->actingAs($user);

        $expense = new Transaction();
        $expense->id = 999;
        $expense->location_id = 1;
        $expense->expense_category_id = 2;
        $expense->ref_no = 'EXP-EDIT-001';
        $expense->transaction_date = '2026-05-20 12:00:00';
        $expense->final_total = 150.00;
        $expense->expense_account = 3;
        $expense->is_vat = 0;
        $expense->additional_notes = 'Test notes';

        $payment = new TransactionPayment();
        $payment->id = 1;
        $payment->method = 'cash';
        $payment->amount = 150.00;
        $expense->setRelation('payment_lines', collect([$payment]));

        $data = [
            'cash_account_id' => 1,
            'expense' => $expense,
            'expense_categories' => collect([2 => 'Office Supplies']),
            'business_locations' => collect([1 => 'Main Location']),
            'users' => [],
            'employees' => collect([]),
            'fleets' => collect([]),
            'taxes' => [
                'tax_rates' => [],
                'attributes' => []
            ],
            'payment_types' => [],
            'account_module' => true,
            'payment_line' => [
                'method' => '',
                'amount' => 0
            ],
            'accounts' => [],
            'current_liabilities_accounts' => collect([]),
            'expense_accounts' => collect([3 => 'Expense Account']),
            'contacts' => []
        ];

        request()->setLaravelSession($this->app['session.store']);

        $html = view('expense.edit', $data)->render();

        // Assert that the edit view contains the table id="expense_items_table"
        $this->assertStringContainsString('id="expense_items_table"', $html);
        // Assert that it contains the category select with the name template attribute
        $this->assertStringContainsString('name="expense_items[${expenseItemIndex}][expense_category_id]"', $html);
    }
}
