<?php

namespace Tests\Feature;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Category;
use App\Product;
use App\ProductVariation;
use App\Variation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Modules\MPCS\Entities\Mpcs9cCashFormSettings;
use Illuminate\Support\Facades\DB;

class MPCS9CCashTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get9ccash_form_returns_data()
    {
        $this->withoutExceptionHandling();
        activity()->disableLogging();

        foreach (['users', 'business', 'business_locations'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Required table {$table} is not available in this test database.");
            }
        }

        $user = User::firstOrCreate(
            ['username' => 'mpcs-9c-cash-test'],
            [
                'first_name' => 'MPCS',
                'last_name' => 'Tester',
                'email' => 'mpcs-9c-cash-test@example.test',
                'password' => bcrypt('password'),
            ]
        );

        $business = Business::firstOrCreate(
            ['name' => 'MPCS 9C Cash Test Business'],
            [
                'currency_id' => 1,
                'owner_id' => $user->id,
                'stop_selling_before' => 0,
                'auto_repair_settings' => '',
                'asset_settings' => '',
                'font_size' => 12,
                'font_family' => 'Arial',
                'weighing_scale_setting' => '',
            ]
        );

        $user->business_id = $business->id;
        $user->save();

        $location = BusinessLocation::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => 'MPCS 9C Cash Test Location',
            ],
            [
                'country' => 'Test',
                'state' => 'Test',
                'city' => 'Test',
                'zip_code' => '00000',
                'invoice_scheme_id' => 1,
                'invoice_layout_id' => 1,
                'default_payment_accounts' => '{}',
            ]
        );

        // Create Setting
        Mpcs9cCashFormSettings::create([
            'business_id' => $business->id,
            'date_time' => '2026-01-01 00:00:00',
            'starting_number' => 1,
            'added_user' => $user->id
        ]);

        $date = '2099-05-15';

        // Create Category
        $category = Category::create([
            'name' => 'Fuel Products',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id,
            'vat_not_applicable' => 0,
        ]);

        // Create Product
        $product = Product::create([
            'name' => 'Antigravity Fuel',
            'business_id' => $business->id,
            'unit_id' => 1,
            'sku' => 'AG-FUEL-' . uniqid(),
            'type' => 'single',
            'tax_type' => 'inclusive',
            'category_id' => $category->id,
            'created_by' => $user->id
        ]);

        $product_variation = ProductVariation::create([
            'name' => 'DUMMY',
            'product_id' => $product->id,
            'is_dummy' => 1,
        ]);

        // Variation
        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => $product->sku,
            'product_variation_id' => $product_variation->id,
            'default_purchase_price' => 100,
            'dpp_inc_tax' => 100,
            'profit_percent' => 0,
            'default_sell_price' => 150,
            'sell_price_inc_tax' => 150
        ]);

        // Create Transaction
        $transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => 1,
            'invoice_no' => 'TEST-INV-001',
            'transaction_date' => $date . ' 10:00:00',
            'created_by' => $user->id,
            'final_total' => 1000
        ]);
        
        \App\TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 10,
            'unit_price' => 100,
            'unit_price_inc_tax' => 100,
            'line_total' => 1000,
            'item_tax' => 0,
            'so_line_id' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['user.business_id' => $business->id])
            ->getJson('/mpcs/get-9ccash-form?start_date=' . $date . '&end_date=' . $date);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsTotal'));
    }

    public function test_get9ccash_form_subtracts_credit_sales_correctly()
    {
        $this->withoutExceptionHandling();
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // Create Setting
        Mpcs9cCashFormSettings::create([
            'business_id' => $business->id,
            'date_time' => '2026-01-01 00:00:00',
            'starting_number' => 1,
            'added_user' => $user->id
        ]);

        $date = '2026-05-15';

        // Create Category
        $category = Category::create([
            'name' => 'Fuel Products',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);

        // Create Product
        $product = Product::create([
            'name' => 'Antigravity Fuel',
            'business_id' => $business->id,
            'unit_id' => 1,
            'sku' => 'AG-FUEL-' . uniqid(),
            'type' => 'single',
            'category_id' => $category->id,
            'created_by' => $user->id
        ]);

        // Variation
        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => $product->sku,
            'default_purchase_price' => 100,
            'dpp_inc_tax' => 100,
            'profit_percent' => 0,
            'default_sell_price' => 100,
            'sell_price_inc_tax' => 100
        ]);

        // Create Settlement
        $settlement = \Modules\Petro\Entities\Settlement::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'settlement_no' => 'SET-999',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        // Create total transaction sale (10 qty, 100 price = 1000 total)
        $transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => 1,
            'invoice_no' => 'TEST-INV-001',
            'transaction_date' => $date . ' 10:00:00',
            'created_by' => $user->id,
            'final_total' => 1000,
            'petro_settlement_id' => $settlement->id
        ]);
        
        \App\TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 10,
            'unit_price' => 100,
            'unit_price_inc_tax' => 100,
            'line_total' => 1000
        ]);

        // Create Credit Sale Payment representing 4 Qty and 400 Amount (needs to be subtracted)
        // Indexed by string 'settlement_no' = 'SET-999'
        DB::table('settlement_credit_sale_payments')->insert([
            'business_id' => $business->id,
            'settlement_no' => 'SET-999',
            'customer_id' => 1,
            'product_id' => $product->id,
            'qty' => 4,
            'price' => 100,
            'amount' => 400,
            'order_date' => $date,
            'order_number' => 'ORD-001',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->actingAs($user)
            ->withSession(['user.business_id' => $business->id])
            ->getJson('/mpcs/get-9ccash-form?start_date=' . $date . '&end_date=' . $date);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Net Qty: 10 - 4 = 6
        $this->assertEquals(6, $data[0]['quantity']);
        // Net Amount: 1000 - 400 = 600
        $this->assertEquals(600, $data[0]['final_total_rs']);
    }

    public function test_form9c_settings_only_returns_active_business_settings()
    {
        $this->withoutExceptionHandling();
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // Clean up any existing settings
        Mpcs9cCashFormSettings::truncate();

        // 1. Create a setting for the active business
        Mpcs9cCashFormSettings::create([
            'business_id' => $business->id,
            'date_time' => '2026-05-18 00:00:00',
            'starting_number' => 100,
            'ref_pre_form_number' => 20000,
            'added_user' => $user->username
        ]);

        // 2. Create a setting for a different business ID
        Mpcs9cCashFormSettings::create([
            'business_id' => $business->id + 99,
            'date_time' => '2026-05-18 00:00:00',
            'starting_number' => 500,
            'ref_pre_form_number' => 50000,
            'added_user' => 'other_user'
        ]);

        // Request settings list (AJAX Datatables call)
        $response = $this->actingAs($user)
            ->withSession(['user.business_id' => $business->id])
            ->getJson('/mpcs/get-form-9c-settings', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        
        // Assert only the active business's setting is returned (recordsTotal = 1)
        $this->assertEquals(1, $response->json('recordsTotal'));
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(100, $data[0]['starting_number']);
    }

    public function test_get9ccash_form_lubricant_credit_sale_does_not_double_count()
    {
        $this->withoutExceptionHandling();
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // Create Setting
        Mpcs9cCashFormSettings::create([
            'business_id' => $business->id,
            'date_time' => '2026-01-01 00:00:00',
            'starting_number' => 1,
            'added_user' => $user->id
        ]);

        $date = '2026-05-15';

        // Create Category
        $category = Category::create([
            'name' => 'Fuel Products',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);

        // Create Product
        $product = Product::create([
            'name' => 'Antigravity Fuel',
            'business_id' => $business->id,
            'unit_id' => 1,
            'sku' => 'AG-FUEL-' . uniqid(),
            'type' => 'single',
            'category_id' => $category->id,
            'created_by' => $user->id
        ]);

        // Variation
        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => $product->sku,
            'default_purchase_price' => 100,
            'dpp_inc_tax' => 100,
            'profit_percent' => 0,
            'default_sell_price' => 100,
            'sell_price_inc_tax' => 100
        ]);

        // Create Settlement
        $settlement = \Modules\Petro\Entities\Settlement::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'settlement_no' => 'SET-999',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        // 1. Create main settlement transaction sale (10 qty, 100 price = 1000 total)
        $main_transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'sub_type' => 'settlement',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => 1,
            'invoice_no' => 'SET-999',
            'transaction_date' => $date . ' 10:00:00',
            'created_by' => $user->id,
            'final_total' => 1000,
            'petro_settlement_id' => $settlement->id
        ]);
        
        \App\TransactionSellLine::create([
            'transaction_id' => $main_transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 10,
            'unit_price' => 100,
            'unit_price_inc_tax' => 100,
            'line_total' => 1000
        ]);

        // 2. Create the separate credit sale transaction (4 qty, 100 price = 400 total)
        $credit_transaction = \App\Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'sub_type' => 'credit_sale',
            'is_credit_sale' => 1,
            'status' => 'final',
            'payment_status' => 'due',
            'contact_id' => 1,
            'invoice_no' => 'SET-999',
            'transaction_date' => $date . ' 10:00:00',
            'created_by' => $user->id,
            'final_total' => 400,
            'petro_settlement_id' => $settlement->id
        ]);
        
        \App\TransactionSellLine::create([
            'transaction_id' => $credit_transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 4,
            'unit_price' => 100,
            'unit_price_inc_tax' => 100,
            'line_total' => 400
        ]);

        // Create Credit Sale Payment representing 4 Qty and 400 Amount (needs to be subtracted)
        // Indexed by string 'settlement_no' = 'SET-999'
        DB::table('settlement_credit_sale_payments')->insert([
            'business_id' => $business->id,
            'settlement_no' => 'SET-999',
            'customer_id' => 1,
            'product_id' => $product->id,
            'qty' => 4,
            'price' => 100,
            'amount' => 400,
            'order_date' => $date,
            'order_number' => 'ORD-001',
            'transaction_id' => $credit_transaction->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $response = $this->actingAs($user)
            ->withSession(['user.business_id' => $business->id])
            ->getJson('/mpcs/get-9ccash-form?start_date=' . $date . '&end_date=' . $date);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        
        // Net Qty: 10 (main) - 4 (credit) = 6
        $this->assertEquals(6, $data[0]['quantity']);
        // Net Amount: 1000 - 400 = 600
        $this->assertEquals(600, $data[0]['final_total_rs']);
    }
}



