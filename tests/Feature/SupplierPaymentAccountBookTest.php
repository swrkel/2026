<?php

namespace Tests\Feature;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\Contact;
use App\Transaction;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SupplierPaymentAccountBookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_supplier_payment_displays_correct_description_and_dates()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create a Supplier
        $supplier = Contact::create([
            'business_id' => $business->id,
            'type' => 'supplier',
            'name' => 'Test Supplier Co',
            'created_by' => $user->id,
        ]);

        // 2. Create a Bank Account
        $bank_account = Account::create([
            'business_id' => $business->id,
            'name' => 'CPC Account Book',
            'account_number' => 'CPC-999',
            'account_type_id' => 1,
            'created_by' => $user->id,
        ]);

        // 3. Create a Supplier Payment Transaction
        $payment_txn = Transaction::create([
            'business_id' => $business->id,
            'type' => 'payment',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => 'PAY-SUP-111',
            'transaction_date' => '2026-05-28 10:00:00',
            'final_total' => 5151.00,
            'created_by' => $user->id,
            'contact_id' => $supplier->id,
        ]);

        // 4. Create TransactionPayment
        $tp = \App\TransactionPayment::create([
            'transaction_id' => $payment_txn->id,
            'business_id' => $business->id,
            'amount' => 5151.00,
            'method' => 'bank_transfer',
            'payment_ref_no' => 'PP2026/0006',
            'paid_on' => '2026-05-28 10:00:00',
            'payment_for' => $supplier->id,
        ]);

        // 5. Create AccountTransaction
        $payment_at = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $bank_account->id,
            'type' => 'credit',
            'amount' => 5151.00,
            'operation_date' => '2026-05-28 10:00:00',
            'created_at' => '2026-05-29 11:30:00',
            'created_by' => $user->id,
            'payment_for' => $supplier->id,
            'transaction_id' => $payment_txn->id,
            'transaction_payment_id' => $tp->id,
        ]);

        // 6. Query AccountController show via AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $bank_account->id . '?start_date=2026-05-20&end_date=2026-05-30&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);

        // Find our payment row
        $foundRow = null;
        foreach ($data as $row) {
            if (isset($row['transaction_id']) && $row['transaction_id'] == $payment_txn->id) {
                $foundRow = $row;
                break;
            }
        }

        $this->assertNotNull($foundRow, "Payment row should be in account book");

        // Assert expected "Suppliers Payment" instead of "Customer Payment"
        $this->assertStringContainsString('Suppliers Payment', $foundRow['description']);
        $this->assertStringNotContainsString('Customer Payment', $foundRow['description']);

        // Assert correct Dates:
        // - operation_date column (Date) should show the correct transaction date (2026-05-28)
        // - realize_date column (Transaction Date) should show the system entered date (2026-05-29)
        $this->assertEquals('2026-05-28', strip_tags($foundRow['operation_date']), "Date column must show operation date");
        $this->assertEquals('2026-05-29', strip_tags($foundRow['realize_date']), "Transaction Date column must show system entered date (created_at)");
    }

    public function test_supplier_payment_for_purchase_transaction_displays_suppliers_payment_description_without_duplicates()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create a Supplier
        $supplier = Contact::create([
            'business_id' => $business->id,
            'type' => 'supplier',
            'name' => 'Supplier Nishan',
            'created_by' => $user->id,
        ]);

        // 2. Create a Bank Account
        $bank_account = Account::create([
            'business_id' => $business->id,
            'name' => 'CPC Account Book',
            'account_number' => 'CPC-111',
            'account_type_id' => 1,
            'created_by' => $user->id,
        ]);

        // 3. Create a Purchase Transaction (not payment)
        $purchase_txn = Transaction::create([
            'business_id' => $business->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'invoice_no' => 'APN5',
            'ref_no' => '212asas',
            'transaction_date' => '2026-05-30 13:20:00',
            'final_total' => 250.00,
            'created_by' => $user->id,
            'contact_id' => $supplier->id,
        ]);

        // 4. Create TransactionPayment
        $tp = \App\TransactionPayment::create([
            'transaction_id' => $purchase_txn->id,
            'business_id' => $business->id,
            'amount' => 250.00,
            'method' => 'cash',
            'payment_ref_no' => 'PP-222',
            'paid_on' => '2026-05-30 13:21:00',
            'payment_for' => $supplier->id,
        ]);

        // 5. Create AccountTransaction representing the payment in the Bank Account
        $payment_at = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $bank_account->id,
            'type' => 'credit',
            'amount' => 250.00,
            'operation_date' => '2026-05-30 13:21:00',
            'created_at' => '2026-05-30 13:21:00',
            'created_by' => $user->id,
            'payment_for' => $supplier->id,
            'transaction_id' => $purchase_txn->id,
            'transaction_payment_id' => $tp->id,
        ]);

        // 6. Query AccountController show via AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $bank_account->id . '?start_date=2026-05-20&end_date=2026-05-31&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);

        // Find our payment row
        $foundRow = null;
        foreach ($data as $row) {
            if (isset($row['transaction_id']) && $row['transaction_id'] == $purchase_txn->id && isset($row['tp_id']) && $row['tp_id'] == $tp->id) {
                $foundRow = $row;
                break;
            }
        }

        $this->assertNotNull($foundRow, "Payment row should be in account book");

        // Assert expected "Suppliers Payment"
        $this->assertStringContainsString('Suppliers Payment', $foundRow['description']);
        
        // Assert correct single payment method "Cash" instead of "CashCash"
        $this->assertStringContainsString('Cash', $foundRow['description']);
        $this->assertStringNotContainsString('CashCash', $foundRow['description']);
    }

    public function test_customer_payment_for_purchase_transaction_displays_customer_payment_description()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Create a Customer (contact type = customer)
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Customer John',
            'created_by' => $user->id,
        ]);

        // 2. Create a Bank Account
        $bank_account = Account::create([
            'business_id' => $business->id,
            'name' => 'CPC Account Book',
            'account_number' => 'CPC-112',
            'account_type_id' => 1,
            'created_by' => $user->id,
        ]);

        // 3. Create a Purchase Transaction with customer (simulating rare case or general validation)
        $purchase_txn = Transaction::create([
            'business_id' => $business->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'invoice_no' => 'APN6',
            'ref_no' => '212asas6',
            'transaction_date' => '2026-05-30 13:20:00',
            'final_total' => 250.00,
            'created_by' => $user->id,
            'contact_id' => $customer->id,
        ]);

        // 4. Create TransactionPayment
        $tp = \App\TransactionPayment::create([
            'transaction_id' => $purchase_txn->id,
            'business_id' => $business->id,
            'amount' => 250.00,
            'method' => 'cash',
            'payment_ref_no' => 'PP-223',
            'paid_on' => '2026-05-30 13:21:00',
            'payment_for' => $customer->id,
        ]);

        // 5. Create AccountTransaction
        $payment_at = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $bank_account->id,
            'type' => 'credit',
            'amount' => 250.00,
            'operation_date' => '2026-05-30 13:21:00',
            'created_at' => '2026-05-30 13:21:00',
            'created_by' => $user->id,
            'payment_for' => $customer->id,
            'transaction_id' => $purchase_txn->id,
            'transaction_payment_id' => $tp->id,
        ]);

        // 6. Query AccountController show via AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $bank_account->id . '?start_date=2026-05-20&end_date=2026-05-31&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);

        // Find our payment row
        $foundRow = null;
        foreach ($data as $row) {
            if (isset($row['transaction_id']) && $row['transaction_id'] == $purchase_txn->id && isset($row['tp_id']) && $row['tp_id'] == $tp->id) {
                $foundRow = $row;
                break;
            }
        }

        $this->assertNotNull($foundRow, "Payment row should be in account book");

        // Assert expected "Customer Payment" instead of "Suppliers Payment"
        $this->assertStringContainsString('Customer Payment', $foundRow['description']);
        $this->assertStringNotContainsString('Suppliers Payment', $foundRow['description']);
    }
}


