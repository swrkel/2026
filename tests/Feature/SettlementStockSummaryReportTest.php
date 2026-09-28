<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Utils\ProductUtil;
use App\Transaction;
use App\TransactionSellLine;
use App\Business;
use App\BusinessLocation;
use App\Product;
use App\ProductVariation;
use App\Variation;
use App\Store;
use App\VariationStoreDetail;
use App\VariationLocationDetails;
use App\Unit;
use App\User;
use App\InvoiceScheme;
use App\InvoiceLayout;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SettlementStockSummaryReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_settlement_sales_are_included_in_stock_summary_report()
    {
        // 1. Create a user first
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
            'username' => 'testuser_test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        // 2. Create a business referencing the user as owner
        $business = Business::create([
            'name' => 'Test Petrol Station',
            'currency_id' => 1,
            'start_date' => '2026-05-17',
            'time_zone' => 'UTC',
            'owner_id' => $user->id,
        ]);

        // 3. Update the user with the business_id
        $user->business_id = $business->id;
        $user->save();

        // Create Invoice Scheme
        $invoice_scheme = InvoiceScheme::create([
            'business_id' => $business->id,
            'name' => 'Default',
            'scheme_type' => 'blank',
            'start_number' => 1,
            'is_default' => 1,
        ]);

        // Create Invoice Layout
        $invoice_layout = InvoiceLayout::create([
            'business_id' => $business->id,
            'name' => 'Default',
            'is_default' => 1,
        ]);

        // 4. Create a location
        $location = BusinessLocation::create([
            'business_id' => $business->id,
            'name' => 'Main Station',
            'location_id' => 'LOC001',
            'invoice_scheme_id' => $invoice_scheme->id,
            'invoice_layout_id' => $invoice_layout->id,
            'default_payment_accounts' => '{"cash":{"is_enabled":"1","account":1},"card":{"is_enabled":"1","account":9},"cheque":{"is_enabled":"1","account":3},"direct_bank_deposit":{"is_enabled":"1","account":2},"bank_transfer":{"is_enabled":"1","account":2}}',
        ]);

        // Set user's location permissions
        $user->location_permissions = json_encode([$location->id]);
        $user->save();

        // 5. Create a unit
        $unit = Unit::create([
            'business_id' => $business->id,
            'actual_name' => 'Liters',
            'short_name' => 'L',
            'allow_decimal' => 1,
        ]);

        // 6. Create a product
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Diesel fuel',
            'type' => 'single',
            'unit_id' => $unit->id,
            'enable_stock' => 1,
        ]);

        // Insert into product_locations
        DB::table('product_locations')->insert([
            'product_id' => $product->id,
            'location_id' => $location->id,
        ]);

        // 7. Create a product variation
        $product_variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
        ]);

        // 8. Create a variation
        $variation = Variation::create([
            'product_id' => $product->id,
            'product_variation_id' => $product_variation->id,
            'name' => 'Diesel fuel',
            'sub_sku' => 'DSL001',
            'default_purchase_price' => 1.00,
            'dpp_inc_tax' => 1.00,
            'profit_percent' => 0.00,
            'default_sell_price' => 1.50,
            'sell_price_inc_tax' => 1.50,
        ]);

        // 9. Create location details for the variation
        VariationLocationDetails::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'qty_available' => 1000.00,
        ]);

        // 10. Create a store for the business
        $store = Store::create([
            'business_id' => $business->id,
            'name' => 'Store 1',
            'location_id' => $location->id,
        ]);

        // 11. Create store details for the variation (the trigger that causes the bug)
        VariationStoreDetail::create([
            'store_id' => $store->id,
            'variation_id' => $variation->id,
            'product_id' => $product->id,
            'qty_available' => 500.00,
        ]);

        // 12. Create a settlement transaction (store_id = null or 0, type = 'sell', sub_type = 'settlement')
        $transaction = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'sub_type' => 'settlement',
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => 1, // dummy customer
            'transaction_date' => '2026-05-17 12:00:00',
            'total_before_tax' => 150.00,
            'final_total' => 150.00,
            'is_settlement' => 1,
        ]);

        // 13. Create a sell line for the transaction
        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 100.00, // Sold 100 Liters of Diesel in settlement
            'unit_price' => 1.50,
            'unit_price_before_discount' => 1.50,
            'unit_price_inc_tax' => 1.50,
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Bind the session driver to current request so permitted_locations() works in CLI tests
        request()->setLaravelSession(app('session')->driver());
        session(['user.business_id' => $business->id]);

        // 14. Call getProductStockDetails with no store_id filter
        $productUtil = new ProductUtil();
        $filters = [
            'location_id' => $location->id,
        ];
        $results = $productUtil->getProductStockDetails($business->id, $filters, 'view_product');

        // 15. Assert that the product variation has 100 total unit sold
        $this->assertCount(1, $results);
        $result_item = $results->first();
        $this->assertEquals($variation->id, $result_item->variation_id);
        $this->assertEquals(100.00, floatval($result_item->total_sold));
    }
}
