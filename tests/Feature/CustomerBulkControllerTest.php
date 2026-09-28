<?php

namespace Tests\Feature;

use App\Business;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\ContactLedger;
use App\AccountTransaction;

class CustomerBulkControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testStoreBulkPayment()
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
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Bulk Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        $t1 = Transaction::create([
            'business_id' => $business->id,
            'contact_id' => $customer->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => 'INV-TEST-B1',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'final_total' => 500.00,
            'created_by' => $user->id
        ]);

        $t2 = Transaction::create([
            'business_id' => $business->id,
            'contact_id' => $customer->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => 'INV-TEST-B2',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'final_total' => 500.00,
            'created_by' => $user->id
        ]);

        $acGroup = \App\AccountGroup::create([
            'business_id' => $business->id,
            'name' => 'Cash Group',
            'account_type_id' => 1
        ]);

        $account = Account::create([
            'business_id' => $business->id,
            'name' => 'Cash Account',
            'account_type_id' => 1,
            'created_by' => $user->id
        ]);

        $response = $this->post(action('CustomerPaymentBulkController@store'), [
            'customer_payment_bulk_payment_method' => $acGroup->id,
            'customer_payment_bulk_payment_ref_no' => 'TEST-001',
            'customer_payment_bulk_customer_id' => $customer->id,
            'customer_payment_bulk_accounting_module' => $account->id,
            'transaction_date' => now()->format('m/d/Y'),
            'payable_amount' => 1000.00,
            'paying' => [
                $t1->id => $t1->id,
                $t2->id => $t2->id
            ],
            'amount' => [
                $t1->id => 500,
                $t2->id => 500
            ],
            'interest' => [
                $t1->id => 0,
                $t2->id => 0
            ]
        ]);

        $response->assertSessionHas('status');
        
        $ledgers = ContactLedger::where('contact_id', $customer->id)->get();
        fwrite(STDERR, "\nCONTACT LEDGERS AFTER BULK:\n");
        foreach($ledgers as $l) {
            fwrite(STDERR, "ID: {$l->id} | Amount: {$l->amount} | Type: {$l->type} | SubType: {$l->sub_type} | Page: {$l->page} | TP ID: {$l->transaction_payment_id}\n");
        }

        $contactUtil = new \App\Utils\ContactUtil(new \App\Utils\ProductUtil());
        $ledger = $contactUtil->getCustomerLedger(
            $customer->id,
            $business->id,
            now()->subDays(1)->format('Y-m-d'),
            now()->addDays(1)->format('Y-m-d')
        );

        fwrite(STDERR, "\nLEDGER OUTPUT:\n");
        foreach ($ledger as $row) {
            fwrite(STDERR, "Type: {$row->type} | SubType: {$row->sub_type} | Invoice: {$row->invoice_no} | Payment Row: {$row->payment_row} | Amount: {$row->amount} | Paid_in_type: {$row->paid_in_type}\n");
        }
        $this->assertTrue(true);
    }
}
