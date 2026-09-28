<?php

namespace Tests\Feature;

use App\Business;
use App\Account;
use App\AccountTransaction;
use App\User;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DirectBankDepositListTest extends TestCase
{
    use DatabaseTransactions;

    public function testDepositListShowsAddedDepositsAndTransfers()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);

        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session(['business.id' => $business->id]);
        session(['user.business_id' => $business->id]);
        session(['user.id' => $user->id]);

        // Create Accounts
        $bankAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Sampath Bank Test',
            'account_number' => '123456789',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        $cashAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Cash Test',
            'account_number' => '987654321',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        // Create a deposit transaction pair
        $credit = AccountTransaction::create([
            'business_id' => $business->id,
            'amount' => 5000.00,
            'account_id' => $bankAccount->id,
            'type' => 'credit',
            'sub_type' => 'deposit',
            'operation_date' => now(),
            'created_by' => $user->id
        ]);

        $debit = AccountTransaction::create([
            'business_id' => $business->id,
            'amount' => 5000.00,
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'sub_type' => 'deposit',
            'transfer_transaction_id' => $credit->id,
            'operation_date' => now(),
            'created_by' => $user->id
        ]);

        $credit->transfer_transaction_id = $debit->id;
        $credit->save();

        // Perform the request to DepositsController@listDepositTransfer
        $response = $this->get('/deposits-module/list-deposit-transfer', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        // Assert that the transaction is present in the DataTable JSON response
        $found = false;
        foreach ($json['data'] as $row) {
            if (strpos($row['amount'], '5,000') !== false || strpos($row['amount'], '5000') !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Added deposit is not present in the deposits table JSON response.");
    }

    public function testDepositListShowsSingleSidedDeposit()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);

        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session(['business.id' => $business->id]);
        session(['user.business_id' => $business->id]);
        session(['user.id' => $user->id]);

        $cashAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Cash Test 2',
            'account_number' => '987654322',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        // Create a deposit transaction with NULL transfer_transaction_id
        $debit = AccountTransaction::create([
            'business_id' => $business->id,
            'amount' => 7800.00,
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'sub_type' => 'deposit',
            'transfer_transaction_id' => null,
            'operation_date' => now(),
            'created_by' => $user->id
        ]);

        // Perform the request to DepositsController@listDepositTransfer
        $response = $this->get('/deposits-module/list-deposit-transfer', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        // Assert that the transaction is present in the DataTable JSON response
        $found = false;
        foreach ($json['data'] as $row) {
            if (strpos($row['amount'], '7,800') !== false || strpos($row['amount'], '7800') !== false) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Single-sided or partially linked deposit is not present in the deposits table JSON response.");
    }

    public function testEditDepositAmountIsReadonly()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);

        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session(['business.id' => $business->id]);
        session(['user.business_id' => $business->id]);
        session(['user.id' => $user->id]);

        $cashAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Cash Test 3',
            'account_number' => '987654323',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        $debit = AccountTransaction::create([
            'business_id' => $business->id,
            'amount' => 8500.00,
            'account_id' => $cashAccount->id,
            'type' => 'debit',
            'sub_type' => 'deposit',
            'transfer_transaction_id' => null,
            'operation_date' => now(),
            'created_by' => $user->id
        ]);

        $response = $this->get('/deposits-module/edit-deposit-transfer/' . $debit->id);

        $response->assertStatus(200);
        $response->assertSee('readonly="readonly"', false);
    }

    public function testChequeDepositShowsChequeNumberAndCustomerName()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);

        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session(['business.id' => $business->id]);
        session(['user.business_id' => $business->id]);
        session(['user.id' => $user->id]);

        $contact = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'John Doe Cheque Depositor',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $transaction = Transaction::create([
            'business_id' => $business->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $contact->id,
            'transaction_date' => now(),
            'total_before_tax' => 12000.00,
            'final_total' => 12000.00,
            'created_by' => $user->id
        ]);

        $payment = TransactionPayment::create([
            'business_id' => $business->id,
            'transaction_id' => $transaction->id,
            'amount' => 12000.00,
            'method' => 'cheque',
            'cheque_number' => 'CHQ-987654',
            'created_by' => $user->id
        ]);

        $chequeAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Cheques in Hand',
            'account_number' => '123123123',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        $bankAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Sampath Bank',
            'account_number' => '456456456',
            'account_type_id' => 1,
            'is_closed' => 0,
            'created_by' => $user->id
        ]);

        // Cheque AccountTransaction
        $chequeTxn = AccountTransaction::create([
            'business_id' => $business->id,
            'amount' => 12000.00,
            'account_id' => $chequeAccount->id,
            'type' => 'debit',
            'sub_type' => 'deposit',
            'transaction_payment_id' => $payment->id,
            'cheque_number' => 'CHQ-987654',
            'operation_date' => now(),
            'created_by' => $user->id
        ]);

        // Deposit this cheque via postChequeDeposit route
        $postData = [
            'account_id' => $bankAccount->id,
            'operation_date' => now()->format('Y-m-d'),
            'select_cheques' => [$chequeTxn->id],
            'from_account' => $chequeAccount->id,
            'note' => 'Cheque Deposit Test note'
        ];

        $postResponse = $this->post('/deposits-module/cheque-deposit', $postData);
        $postResponse->assertRedirect();

        // Perform the request to list-deposit-transfer
        $response = $this->get('/deposits-module/list-deposit-transfer', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        // Assert that the transaction is present in the DataTable JSON response,
        // and it has John Doe Cheque Depositor as customer_name, and CHQ-987654 as cheque_number
        $found = false;
        foreach ($json['data'] as $row) {
            if (strpos($row['cheque_number'], 'CHQ-987654') !== false) {
                $this->assertEquals('John Doe Cheque Depositor', $row['customer_name']);
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Cheque deposit transaction was not found or lacks correct cheque number / customer name.");
    }
}
