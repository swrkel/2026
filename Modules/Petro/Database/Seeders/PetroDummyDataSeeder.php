<?php

namespace Modules\Petro\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Category;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use App\User;
use App\Transaction;
use App\AccountTransaction;
use App\Account;
use Carbon\Carbon;

class PetroDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $business_id = 1; // Default to business ID 1
        
        // Ensure business exists
        $business = Business::find($business_id);
        if (!$business) {
            $this->command->info("Business ID $business_id not found. Creating one...");
            $business = Business::create([
                'name' => 'Demo Petrol Station',
                'currency_id' => 1,
                'start_date' => Carbon::now(),
                'owner_id' => 1
            ]);
            $business_id = $business->id;
        }

        // Get or Create Location
        $location = BusinessLocation::where('business_id', $business_id)->first();
        if (!$location) {
            $location = BusinessLocation::create([
                'business_id' => $business_id,
                'name' => 'Main Station',
                'location_id' => 'LOC001',
                'landmark' => 'Downtown',
                'city' => 'Metropolis',
                'zip_code' => '10001',
                'state' => 'NY',
                'country' => 'USA'
            ]);
        }
        $location_id = $location->id;

        // Ensure Account Groups exist (Crucial for Settlement SW dropdowns)
        $account_groups = [
            'Bank Account', 'Card', 'Cheque', 'Cash', 'Loans Given', 'Owners Drawings'
        ];
        foreach ($account_groups as $group_name) {
            $group = \App\AccountGroup::where('business_id', $business_id)->where('name', $group_name)->first();
            if (!$group) {
                \App\AccountGroup::create([
                    'business_id' => $business_id,
                    'name' => $group_name
                ]);
                $this->command->info("Created Account Group: $group_name");
            }
        }

        // Ensure a Fuel Category exists
        $category = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();
        if (!$category) {
            $category = Category::create([
                'business_id' => $business_id,
                'name' => 'Fuel',
                'short_code' => 'FUEL',
                'created_by' => 1
            ]);
        }

        // Ensure a Fuel Product exists
        $fuel_product = Product::where('business_id', $business_id)->where('category_id', $category->id)->first();
        if (!$fuel_product) {
            $fuel_product = Product::create([
                'business_id' => $business_id,
                'name' => 'Super Petrol',
                'sku' => 'FUEL001',
                'type' => 'single',
                'tax_type' => 'inclusive',
                'category_id' => $category->id,
                'enable_stock' => 1,
                'created_by' => 1
            ]);
        }

        // 1. Create Fuel Tanks
        $fuel_tanks = ['Petrol Tank 1', 'Diesel Tank 1'];
        $tank_ids = [];
        
        foreach ($fuel_tanks as $tank_name) {
            $existing_tank = FuelTank::where('business_id', $business_id)->where('fuel_tank_number', $tank_name)->first();
            
            if (!$existing_tank) {
                
                $tank = FuelTank::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'fuel_tank_number' => $tank_name,
                    'product_id' => $fuel_product ? $fuel_product->id : null,
                    'current_balance' => 10000.00,
                    'bulk_tank' => 0,
                    'user_id' => 1, // Required
                    'transaction_date' => Carbon::now()
                ]);
                $tank_ids[] = $tank->id;
                $this->command->info("Created Fuel Tank: $tank_name");
            } else {
                $tank_ids[] = $existing_tank->id;
                $this->command->info("Fuel Tank already exists: $tank_name");
            }
        }

        // 2. Create Pumps
        $pumps = ['Pump 01', 'Pump 02', 'Pump 03', 'Pump 04'];
        $pump_ids = [];
        
        foreach ($pumps as $index => $pump_name) {
            $existing_pump = Pump::where('business_id', $business_id)->where('pump_name', $pump_name)->first();
            
            if (!$existing_pump) {
                $tank_id = $tank_ids[$index % count($tank_ids)]; // Distribute pumps to tanks
                
                $pump = Pump::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'pump_name' => $pump_name,
                    'pump_no' => $index + 1,
                    'fuel_tank_id' => $tank_id,
                    'product_id' => FuelTank::find($tank_id)->product_id,
                    'starting_meter' => 1000, // Correct column name
                    'qty' => '0', // Required
                    'transaction_date' => Carbon::now(),
                    'installation_date' => Carbon::now()->format('Y-m-d')
                ]);
                $pump_ids[] = $pump->id;
                $this->command->info("Created Pump: $pump_name");
            } else {
                $pump_ids[] = $existing_pump->id;
                $this->command->info("Pump already exists: $pump_name");
            }
        }

        // 3. Create Pump Operators
        $operators = ['John Doe', 'Jane Smith', 'Mike Johnson'];
        $operator_ids = [];

        foreach ($operators as $name) {
            $existing_operator = PumpOperator::where('business_id', $business_id)->where('name', $name)->first();

            if (!$existing_operator) {
                // Get a valid pump ID
                $default_pump_id = !empty($pump_ids) ? $pump_ids[0] : null; 
                
                // If no pumps created yet (shouldn't happen), assume 1? Or fail? 
                // We just created pumps, so $pump_ids should be populated.
                
                $operator = PumpOperator::create([
                    'business_id' => $business_id,
                    'name' => $name,
                    'location_id' => $location_id,
                    'address' => '123 Fake St',
                    'dob' => '1990-01-01',
                    'mobile' => '1234567890',
                    'active' => 1,
                    // Required fields based on schema
                    'pump_id' => $default_pump_id, 
                    'assigned_pump_id' => $default_pump_id, 
                    'commission_type' => 'fixed',
                    'commission_ap' => 0.00
                ]);
                $operator_ids[] = $operator->id;
                $this->command->info("Created Pump Operator: $name");
            } else {
                $operator_ids[] = $existing_operator->id;
                $this->command->info("Pump Operator already exists: $name");
            }
        }

        // 4. Create an Open Settlement for the first operator
        $operator_id = $operator_ids[0];
        $settlement_no = 'SET-' . time();
        
        $existing_settlement = Settlement::where('business_id', $business_id)->where('status', 0)->where('pump_operator_id', $operator_id)->first();
        
        if (!$existing_settlement) {
            $settlement = Settlement::create([
                'settlement_no' => $settlement_no,
                'business_id' => $business_id,
                'transaction_date' => Carbon::now(),
                'location_id' => $location_id,
                'pump_operator_id' => $operator_id,
                'bulk_store_product' => 0,
                'work_shift' => 'Shift 1',
                'status' => 0, // Open/Draft
                'total_amount' => 250.00,
                'is_edit' => 0
            ]);
            $this->command->info("Created Open Settlement: $settlement_no");

            // 5. Create a Meter Sale for this settlement
            $pump_id = $pump_ids[0];
            $product_id = FuelTank::where('business_id', $business_id)->first()->product_id;
            
            MeterSale::create([
                'settlement_no' => $settlement->id, // Usually stores ID reference or Settlement No string? Migration says string. 
                                                   // Controller often uses settlement->id for relationships. 
                                                   // Let's use settlement_no string as per column name, but check controller usage.
                                                   // Controller at 3522: 'settlement_no' => $settlement->id
                                                   // Controller at 6330: 'settlement_no' => $settlement->id
                                                   // So MeterSale settlement_no column might contain the ID?
                                                   // Schema says varchar(255).
                                                   // Let's check existing MeterSale data if possible.
                                                   // Given code: $active_settlement->meter_sales relation.
                                                   // Let's rely on how ID is usually passed.
                                                   // Actually, let's use the ID because the relationship likely maps it.
                'business_id' => $business_id,
                'product_id' => $product_id,
                'pump_id' => $pump_id,
                'starting_meter' => 1000,
                'closing_meter' => 1100,
                'price' => 2.50,
                'qty' => 100,
                'sub_total' => 250.00,
                'discount_amount' => 0
            ]);
            $this->command->info("Created Meter Sale for Settlement");
        } else {
            $settlement = $existing_settlement;
            $this->command->info("Open Settlement already exists for operator.");
        }

        // 6. Create Pump Operator Meter Sale (for Pumper Dashboard)
        $existing_pumper_sale = PumpOperatorMeterSale::where('business_id', $business_id)
            ->where('pump_operator_id', $operator_id)
            ->whereDate('date_time', Carbon::now()->toDateString())
            ->first();

        if (!$existing_pumper_sale) {
            $pumper_sale = PumpOperatorMeterSale::create([
                'business_id' => $business_id,
                'pump_operator_id' => $operator_id,
                'date_time' => Carbon::now(),
                'amount' => 250.00, // Total amount
                'deposited' => 0.00,
                'balance' => 250.00,
                 // Assuming collection_form_no is needed if linked to payments, but maybe optional
            ]);
            $this->command->info("Created Pump Operator Meter Sale");

            // Detail
            $pump_id = $pump_ids[0];
            $product_id = FuelTank::where('business_id', $business_id)->first()->product_id;
            
            PumpOperatorMeterSaleDetail::create([
                'sale_id' => $pumper_sale->id,
                'business_id' => $business_id,
                'pump_id' => $pump_id,
                'received_meter' => 1000, // Opening
                'new_meter' => 1100,    // Closing
                'sold_qty' => 100,
                'unit_price' => 2.50,
                'amount' => 250.00,
            ]);
            $this->command->info("Created Pump Operator Meter Sale Detail");

        } else {
            $this->command->info("Pump Operator Meter Sale already exists for today.");
            
            // Ensure detail exists (in case previous run failed midway)
            $pumper_sale = $existing_pumper_sale;
            $pump_id = $pump_ids[0];
            $detail_exists = PumpOperatorMeterSaleDetail::where('sale_id', $pumper_sale->id)->exists();
            
            if (!$detail_exists) {
                 PumpOperatorMeterSaleDetail::create([
                    'sale_id' => $pumper_sale->id,
                    'business_id' => $business_id,
                    'pump_id' => $pump_id,
                    'received_meter' => 1000,
                    'new_meter' => 1100,
                    'sold_qty' => 100,
                    'unit_price' => 2.50,
                    'amount' => 250.00,
                ]);
                $this->command->info("Created missing Pump Operator Meter Sale Detail.");
            }
        }
        


        $pump_operator = PumpOperator::where('business_id', $business_id)->first();
        if ($pump_operator) {
            $pump_operator_id = $pump_operator->id;
            $user = User::where('business_id', $business_id)->first();
            $location = BusinessLocation::where('business_id', $business_id)->first();

            if($user && $location) {
        // --- Create Dummy Shortage (Debit 50.00) ---
        $shortage_exists = Transaction::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('type', 'settlement')
            ->where('sub_type', 'shortage')
            ->exists();

        if (!$shortage_exists) {
            // Need a dummy account
            $account = Account::firstOrCreate(
                ['business_id' => $business_id, 'name' => 'Cash'],
                ['account_number' => '123456', 'account_type_id' => 1] // Assuming 1 is valid or handled
            );

            // 1. Transaction (Shortage)
            $shortage_txn = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location->id,
                'type' => 'settlement',
                'sub_type' => 'shortage',
                'status' => 'final',
                'payment_status' => 'due',
                'pump_operator_id' => $pump_operator_id,
                'transaction_date' => Carbon::now(),
                'total_before_tax' => 50.00,
                'final_total' => 50.00,
                'created_by' => $user->id,
            ]);

            // 2. Account Transaction (Debit for Shortage)
            // Note: getPumpOperatorLedgerSummary sums DEBIT for shortage usually (lines 317 in Controller says 'total_debit_for_period')
            AccountTransaction::create([
                'business_id' => $business_id,
                'account_id' => $account->id,
                'transaction_id' => $shortage_txn->id,
                'amount' => 50.00,
                'type' => 'debit', // Shortage = Operator owes us = Debit
                'sub_type' => 'ledger_show',
                'operation_date' => Carbon::now(),
                'created_by' => $user->id,
            ]);

            $this->command->info('Created dummy Shortage (50.00).');
        }

        // --- Create Dummy Excess (Credit 20.00) ---
        $excess_exists = Transaction::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('type', 'settlement')
            ->where('sub_type', 'excess')
            ->exists();

        if (!$excess_exists) {
            $account = Account::firstOrCreate(
                ['business_id' => $business_id, 'name' => 'Cash']
            );

            // 1. Transaction (Excess)
            $excess_txn = Transaction::create([
                'business_id' => $business_id,
                'location_id' => $location->id,
                'type' => 'settlement',
                'sub_type' => 'excess', // Typo fixed from 'excess' to 'excess'
                'status' => 'final',
                'payment_status' => 'due',
                'pump_operator_id' => $pump_operator_id,
                'transaction_date' => Carbon::now(),
                'total_before_tax' => 20.00,
                'final_total' => 20.00,
                'created_by' => $user->id,
            ]);

            // 2. Account Transaction (Credit for Excess)
            AccountTransaction::create([
                'business_id' => $business_id,
                'account_id' => $account->id,
                'transaction_id' => $excess_txn->id,
                'amount' => 20.00,
                'type' => 'credit', // Excess = We owe Operator = Credit
                'sub_type' => 'ledger_show',
                'operation_date' => Carbon::now(),
                'created_by' => $user->id,
            ]);

            $this->command->info('Created dummy Excess (20.00).');
        }

        // Create PumpOperatorAssignment and PumperDayEntry for testing
        // Use the first pump and first pump operator
        if (!empty($pump_ids) && $pump_operator) {
            $pump_id = $pump_ids[0];
            
            // Check if assignment exists
            $assignment = \Modules\Petro\Entities\PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator->id)
                ->where('pump_id', $pump_id)
                ->whereDate('date_and_time', Carbon::now())
                ->first();

            if (!$assignment) {
                $assignment = \Modules\Petro\Entities\PumpOperatorAssignment::create([
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator->id,
                    'pump_id' => $pump_id,
                    'shift_id' => null, // Or create a shift if needed
                    'shift_number' => 1,
                    'date_and_time' => Carbon::now(),
                    'starting_meter' => 1000.000,
                    'closing_meter' => 1500.000,
                    'status' => 'open',
                    'is_confirmed' => 0,
                ]);
                $this->command->info('Created dummy PumpOperatorAssignment.');
            }

            // Check if day entry exists
            $day_entry = \Modules\Petro\Entities\PumperDayEntry::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator->id)
                ->where('pump_id', $pump_id)
                ->whereDate('date', Carbon::now())
                ->first();

            if (!$day_entry) {
                \Modules\Petro\Entities\PumperDayEntry::create([
                    'business_id' => $business_id,
                    'pump_operator_id' => $pump_operator->id,
                    'pumper_assignment_id' => $assignment->id,
                    'pump_id' => $pump_id,
                    'date' => Carbon::now(),
                    'starting_meter' => 1000.000,
                    'closing_meter' => 1500.000,
                    'sold_ltr' => 500.000,
                    'amount' => 50000.00, // Approx
                    'testing_ltr' => 0.000,
                    'settlement_no' => 'SET-test-01',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                $this->command->info('Created dummy PumperDayEntry.');
            }
        }

        // 7. Create Dummy Credit Sales for Duplicate Testing
        // Ensure a customer exists
        $customer = \App\Contact::where('business_id', $business_id)->where('type', 'customer')->first();
        if (!$customer) {
            $customer = \App\Contact::create([
                'business_id' => $business_id,
                'type' => 'customer',
                'name' => 'Test Customer',
                'created_by' => 1
            ]);
        }

        if ($operator_id) {
            $product_id = \App\Product::where('business_id', $business_id)->first()->id;
            if($product_id){
             // Create a Daily Voucher first
             $voucher = \Modules\Petro\Entities\DailyVoucher::create([
                 'business_id' => $business_id,
                 'location_id' => $location_id,
                 'operator_id' => $operator_id, 
                 'customer_id' => $customer->id,
                 'transaction_date' => Carbon::now()->format('Y-m-d'),
                 'voucher_order_date' => Carbon::now()->format('Y-m-d'),
                 'voucher_order_number' => 'ORD-001',
                 'total_amount' => 500.00,
                 'daily_vouchers_no' => 1,
                 'vehicle_no' => 123, // dummy
                 'status' => 0,
                 'created_by' => 1,
                 'is_issue_customer_bill' => 0, // default
             ]);

             // Create existing SettlementCreditSalePayment (representing a saved one).
             // Wrapped in withBypass: seeders may write directly without going through Reconciler.
             \Modules\Petro\Services\SettlementPaymentReconciler::withBypass(function () use ($business_id, $settlement, $customer, $product_id, $operator_id, $voucher) {
                 \Modules\Petro\Entities\SettlementCreditSalePayment::create([
                     'business_id' => $business_id,
                     'settlement_no' => $settlement->id,
                     'customer_id' => $customer->id,
                     'product_id' => $product_id,
                     'order_number' => 'ORD-001',
                     'order_date' => \Carbon::now()->format('Y-m-d'),
                     'price' => 50.00,
                     'qty' => 10,
                     'amount' => 500.00,
                     'pump_operator_id' => $operator_id,
                     'daily_voucher_id' => $voucher->id
                 ]);
             });
             $this->command->info("Created Dummy Credit Sale Payment (linked to voucher).");
        }
    }
}
}
}
}
