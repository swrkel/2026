<?php

namespace Tests\Feature;

use App\Business;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\ContactLedger;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerBulkPaymentLedgerDeduplicationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureBusinessFixture();
    }

    protected function ensureBusinessFixture(): void
    {
        if (Business::query()->exists()) {
            return;
        }

        $currencyId = DB::table('currencies')->orderBy('id')->value('id');
        if (!$currencyId) {
            $currencyId = DB::table('currencies')->insertGetId([
                'country' => 'Test',
                'currency' => 'Test Rupee',
                'code' => 'TST',
                'symbol' => 'Rs',
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Bulk',
            'last_name' => 'Tester',
            'username' => 'bulk_tester_' . uniqid(),
            'email' => 'bulk_tester_' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'Bulk Test Business',
            'currency_id' => $currencyId,
            'owner_id' => $userId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $userId)->update(['business_id' => $businessId]);

        DB::table('business_locations')->insert([
            'business_id' => $businessId,
            'name' => 'Bulk Test Location',
            'country' => 'Test',
            'state' => 'Test',
            'city' => 'Test',
            'zip_code' => '00000',
            'invoice_scheme_id' => 1,
            'invoice_layout_id' => 1,
            'default_payment_accounts' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function testCustomerBulkPaymentLedgerDeduplication()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // Create a customer
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Test Customer',
            'mobile' => '1234567890',
            'created_by' => $user->id
        ]);

        // Create credit sales (transactions)
        $t1 = Transaction::create([
            'business_id' => $business->id,
            'contact_id' => $customer->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => 'INV-001',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'final_total' => 100.00,
            'created_by' => $user->id
        ]);

        $t2 = Transaction::create([
            'business_id' => $business->id,
            'contact_id' => $customer->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'invoice_no' => 'INV-002',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'final_total' => 150.00,
            'created_by' => $user->id
        ]);

        // Mock a bulk payment request
        // We will hit the store method of CustomerPaymentBulkController
        $sessionData = [
            'user' => [
                'id' => $user->id,
                'business_id' => $business->id,
            ],
            'business' => [
                'id' => $business->id,
            ]
        ];

        // First, check how many entries are in the ledger before
        $contactUtil = new \App\Utils\ContactUtil(new \App\Utils\ProductUtil());
        
        $response = $this->withSession($sessionData)->post(action('CustomerPaymentBulkController@store'), [
            'customer_payment_bulk_customer_id' => $customer->id,
            'customer_payment_bulk_payment_ref_no' => '12345',
            'daily_shift_no' => '1',
            'customer_payment_bulk_accounting_module' => null,
            'transaction_date' => now()->format('Y-m-d H:i:s'),
            'payable_amount' => 250.00,
            'paying' => [
                $t1->id => 'on',
                $t2->id => 'on',
            ],
            'amount' => [
                $t1->id => 100.00,
                $t2->id => 150.00,
            ],
            'interest' => [
                $t1->id => 0,
                $t2->id => 0,
            ],
            'method' => 'cash',
        ]);

        $response->assertRedirect();

        // Let's print all contact ledger records
        fwrite(STDERR, "\nAll ContactLedger records in DB:\n");
        foreach (\App\ContactLedger::all() as $cl) {
            fwrite(STDERR, sprintf("CL ID: %s, contact_id: %s, type: %s, sub_type: %s, amount: %s, transaction_id: %s, transaction_payment_id: %s, page: %s, note: %s\n",
                $cl->id, $cl->contact_id, $cl->type, $cl->sub_type, $cl->amount, $cl->transaction_id, $cl->transaction_payment_id, $cl->page, $cl->note
            ));
        }

        // Get the ledger transactions
        $ledger = $contactUtil->getCustomerLedger($customer->id, $business->id, now()->subDays(1)->format('Y-m-d'), now()->addDays(1)->format('Y-m-d'));
        
        // Let's print the ledger transactions for debugging
        fwrite(STDERR, "\nLedger transactions count: " . $ledger->count() . "\n");
        foreach ($ledger as $item) {
            fwrite(STDERR, sprintf("Type: %s, Sub Type: %s, Amount: %s, Invoice No: %s, Payment Row: %s\n",
                $item->type, $item->sub_type, $item->amount, $item->invoice_no, $item->payment_row
            ));
        }

        // Count how many 'payment' type ledger transactions we have with amount 250
        $paymentCount = $ledger->filter(function($row) {
            return in_array($row->type, ['payment', 'customer_payment']);
        })->count();

        $this->assertEquals(1, $paymentCount, "Expected exactly 1 payment transaction in the ledger.");
    }
}
