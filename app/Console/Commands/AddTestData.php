<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Transaction;
use App\Contact;
use App\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AddTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:add-credit-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add test credit sales data for F9C form testing';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $business_id = 1;

        // Create a sample customer
        $customer = Contact::firstOrCreate([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Test Customer 1'
        ], [
            'supplier_business_name' => 'Test Customer 1',
            'created_by' => 1
        ]);

        // Create a sample product
        $product = Product::firstOrCreate([
            'business_id' => $business_id,
            'name' => 'Test Product',
            'type' => 'single'
        ], [
            'sku' => 'TEST-001',
            'created_by' => 1
        ]);

        $this->info("Customer created/found: " . $customer->id);
        $this->info("Product created/found: " . $product->id);

        // Create settlement credit sale payment records first
        $settlement1 = DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $business_id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 10.00,
            'price' => 150.00,
            'amount' => 1500.00,
            'order_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'order_number' => 'ORD-001',
            'settlement_no' => 'SET-001',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $settlement2 = DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $business_id,
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'qty' => 16.67,
            'price' => 150.00,
            'amount' => 2500.00,
            'order_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'order_number' => 'ORD-002',
            'settlement_no' => 'SET-002',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Create sample credit sale transactions
        $transaction1 = Transaction::create([
            'business_id' => $business_id,
            'location_id' => 1,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $customer->id,
            'transaction_date' => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
            'invoice_no' => 'INV-001',
            'ref_no' => 'REF-001',
            'final_total' => 1500.00,
            'is_credit_sale' => 1,
            'credit_sale_id' => $settlement1,
            'created_by' => 1
        ]);

        $transaction2 = Transaction::create([
            'business_id' => $business_id,
            'location_id' => 1,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $customer->id,
            'transaction_date' => Carbon::now()->subDays(1)->format('Y-m-d H:i:s'),
            'invoice_no' => 'INV-002',
            'ref_no' => 'REF-002',
            'final_total' => 2500.00,
            'is_credit_sale' => 1,
            'credit_sale_id' => $settlement2,
            'created_by' => 1
        ]);

        $this->info("Created settlement records: " . $settlement1 . ", " . $settlement2);
        $this->info("Created transactions: " . $transaction1->id . ", " . $transaction2->id);
        $this->info("Test data added successfully!");
        $this->info("Now check the F9C form - it should show these credit sales.");
    }
}
