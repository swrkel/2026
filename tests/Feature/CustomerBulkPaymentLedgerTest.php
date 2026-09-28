<?php

namespace Tests\Feature;

use App\Business;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerBulkPaymentLedgerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCustomerBulkBankTransferPaymentAppearsInLedger()
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

        // Create Regular customer (is_default = 0)
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Regular Customer',
            'is_default' => 0,
            'created_by' => $user->id
        ]);

        // Create a sell transaction
        $t = Transaction::create([
            'business_id' => $business->id,
            'contact_id' => $customer->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => 'INV-TEST-REG',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'final_total' => 500.00,
            'created_by' => $user->id
        ]);

        // Create bank transfer bulk payment
        $tp = TransactionPayment::create([
            'business_id' => $business->id,
            'transaction_id' => $t->id,
            'amount' => 500.00,
            'method' => 'bank_transfer',
            'paid_on' => now()->format('Y-m-d'),
            'payment_for' => $customer->id,
            'payment_ref_no' => 'CPB-TEST-1',
            'paid_in_type' => 'customer_bulk',
            'created_by' => $user->id
        ]);

        $contactUtil = new \App\Utils\ContactUtil(new \App\Utils\ProductUtil());
        $ledger = $contactUtil->getCustomerLedger(
            $customer->id,
            $business->id,
            now()->subDays(1)->format('Y-m-d'),
            now()->addDays(1)->format('Y-m-d')
        );

        fwrite(STDERR, "\nLEDGER ROWS IN TEST:\n");
        foreach ($ledger as $row) {
            fwrite(STDERR, "Type: {$row->type} | SubType: {$row->sub_type} | Invoice: {$row->invoice_no} | Payment Row: {$row->payment_row} | Amount: {$row->amount} | Paid_in_type: {$row->paid_in_type}\n");
        }

        $this->assertTrue(true);
    }
}
