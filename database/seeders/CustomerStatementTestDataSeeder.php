<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerStatementTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $business_id = 3; // Ishadi2
        $location_id = 3; // Ishadi2 Location
        $created_by = 7; // User: ishadi2
        $product_id = 6; // Petrol 92
        $variation_id = 6; // Petrol 92 Dummy Variation

        // Get customers for business_id 3
        $customers = DB::table('contacts')
            ->where('business_id', $business_id)
            ->where('type', 'customer')
            ->whereIn('name', ['Customer - 1', 'Customer - 2', 'Customer - 3'])
            ->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers named Customer - 1, 2, or 3 found for business ID 3.');
            return;
        }

        // Clean up previous test statements & details
        $oldStatements = DB::table('customer_statements')
            ->where('business_id', $business_id)
            ->where('statement_no', 'like', 'TEST-STMT-%')
            ->get();

        foreach ($oldStatements as $stmt) {
            DB::table('customer_statement_details')->where('statement_id', $stmt->id)->delete();
            DB::table('customer_statements')->where('id', $stmt->id)->delete();
        }

        // Clean up previous test transactions (both raw and statement-linked ones)
        $oldTxns = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where(function ($query) {
                $query->where('invoice_no', 'like', 'INV-TEST-%')
                      ->orWhere('invoice_no', 'like', 'INV-STMT-%');
            })
            ->get();

        foreach ($oldTxns as $txn) {
            DB::table('transaction_sell_lines')->where('transaction_id', $txn->id)->delete();
            DB::table('transactions')->where('id', $txn->id)->delete();
        }

        foreach ($customers as $customer) {
            $this->command->info("Seeding data for customer: {$customer->name} (ID: {$customer->id})");

            // ==========================================
            // 1. Seed 3 Raw Transactions (First Tab: Customer Statements)
            // ==========================================
            $dates = [
                '2026-05-05 10:30:00',
                '2026-05-12 14:15:00',
                '2026-05-20 09:00:00',
            ];

            foreach ($dates as $index => $date) {
                $invIndex = $index + 1;
                $invoice_no = "INV-TEST-{$customer->id}-{$invIndex}";
                $qty = 10 * $invIndex;
                $unit_price = 250.00;
                $final_total = $qty * $unit_price;

                // Insert transaction
                $transaction_id = DB::table('transactions')->insertGetId([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'contact_id' => $customer->id,
                    'type' => 'sell',
                    'status' => 'final',
                    'payment_status' => 'due',
                    'invoice_no' => $invoice_no,
                    'ref_no' => "REF-TEST-{$customer->id}-{$invIndex}",
                    'transaction_date' => $date,
                    'final_total' => $final_total,
                    'created_by' => $created_by,
                    'exchange_rate' => 1.00,
                    'is_settlement' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Insert sell line
                DB::table('transaction_sell_lines')->insert([
                    'transaction_id' => $transaction_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $qty,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price,
                    'unit_price_before_discount' => $unit_price,
                    'line_total' => $final_total,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            // ==========================================
            // 2. Seed 1 Saved Statement (Second Tab: List Customer Statements)
            // ==========================================
            $stmt_no = "TEST-STMT-{$customer->id}";
            $statement_id = DB::table('customer_statements')->insertGetId([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'customer_id' => $customer->id,
                'statement_no' => $stmt_no,
                'print_date' => '2026-05-25',
                'date_from' => '2026-05-01',
                'date_to' => '2026-05-30',
                'added_by' => $created_by,
                'is_transaction_linked' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Seed 2 linked transactions for the statement (so they won't appear in the first tab but show as linked)
            $stmt_txn_dates = [
                '2026-05-02 08:00:00',
                '2026-05-10 11:30:00',
            ];

            foreach ($stmt_txn_dates as $index => $date) {
                $invIndex = $index + 1;
                $invoice_no = "INV-STMT-{$customer->id}-{$invIndex}";
                $qty = 15 * $invIndex;
                $unit_price = 200.00;
                $final_total = $qty * $unit_price;

                $transaction_id = DB::table('transactions')->insertGetId([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'contact_id' => $customer->id,
                    'type' => 'sell',
                    'status' => 'final',
                    'payment_status' => 'due',
                    'invoice_no' => $invoice_no,
                    'ref_no' => "REF-STMT-{$customer->id}-{$invIndex}",
                    'transaction_date' => $date,
                    'final_total' => $final_total,
                    'created_by' => $created_by,
                    'exchange_rate' => 1.00,
                    'is_settlement' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                DB::table('transaction_sell_lines')->insert([
                    'transaction_id' => $transaction_id,
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $qty,
                    'unit_price' => $unit_price,
                    'unit_price_inc_tax' => $unit_price,
                    'unit_price_before_discount' => $unit_price,
                    'line_total' => $final_total,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Insert into customer_statement_details
                DB::table('customer_statement_details')->insert([
                    'business_id' => $business_id,
                    'statement_id' => $statement_id,
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'location' => 'Ishadi2',
                    'invoice_no' => $invoice_no,
                    'customer_reference' => null,
                    'order_no' => null,
                    'vehicle_number' => '',
                    'route_name' => '',
                    'order_date' => null,
                    'product' => 'Petrol 92',
                    'unit_price' => $unit_price,
                    'qty' => $qty,
                    'invoice_amount' => $final_total,
                    'due_amount' => $final_total,
                    'transaction_id' => $transaction_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            $this->command->info("Created saved statement: {$stmt_no} (ID: {$statement_id})");
        }

        $this->command->info('Customer statement test data seeded successfully!');
    }
}
