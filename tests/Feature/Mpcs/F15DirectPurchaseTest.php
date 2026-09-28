<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Category;
use App\Product;
use App\Variation;
use App\Transaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\MPCS\Entities\FormF18Header;
use Modules\MPCS\Entities\FormF18Detail;
use Modules\MPCS\Entities\MpcsF15CategorySelection;
use Modules\MPCS\Services\FormHelper;
use Illuminate\Support\Facades\DB;

class F15DirectPurchaseTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_f15_direct_purchase_and_sales_resolution()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business');
        }
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // 1. Create Category & Subcategory
        $category = Category::create([
            'name' => 'F15 Active Category 2',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);

        $subCategory = Category::create([
            'name' => 'F15 Active SubCategory 2',
            'business_id' => $business->id,
            'parent_id' => $category->id,
            'created_by' => $user->id
        ]);

        // 2. Select this sub-category in F15 Category Selections
        MpcsF15CategorySelection::create([
            'business_id' => $business->id,
            'category_ids' => [$subCategory->id],
            'created_by' => $user->id
        ]);

        // 3. Create a Product
        $product = Product::create([
            'name' => 'F16 Product 2',
            'business_id' => $business->id,
            'type' => 'single',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'sku' => 'F16-SKU-992',
            'tax' => 1,
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);

        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => 'F16-SKU-992',
            'default_purchase_price' => 100.00,
            'dpp_inc_tax' => 100.00,
            'default_sell_price' => 150.00,
            'sell_price_inc_tax' => 150.00,
        ]);

        // 4. Create F16A Purchase
        $purchaseTrans = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-15 10:00:00',
            'total_before_tax' => 1000.00,
            'final_total' => 1000.00,
            'ref_no' => 'INV-F16A-TEST',
            'created_by' => $user->id,
        ]);

        DB::table('purchase_lines')->insert([
            'transaction_id' => $purchaseTrans->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 10,
            'purchase_price' => 100.00,
            'purchase_price_inc_tax' => 100.00,
        ]);

        DB::table('form_f16_details')->insert([
            'transaction_id' => $purchaseTrans->id,
            'form_no' => 777,
            'invoice_no' => 'INV-F16A-TEST',
            'supplier' => 'Test Supplier',
            'this_form_total' => '1500.00',
            'last_form_total' => '0',
            'grand_total' => '1500.00',
            'book_no' => 'B1',
            'book_stock' => '10',
            'this_book' => '0',
            'prev_book' => '0',
            'grand_book' => '0',
        ]);

        // 5. Create F18 Purchase (received sale total = 2000.00)
        $f18PrefixId = \DB::table('form_f18_prefix_numbers')->insertGetId([
            'business_id' => $business->id,
            'opening_date' => '2026-05-01',
            'prefix' => 'F18-TEST2',
            'starting_number' => 600,
            'transferred_locations' => json_encode([$location->name]),
            'created_by' => $user->id,
        ]);

        $f18Header = FormF18Header::create([
            'business_id' => $business->id,
            'form_no' => 'F18-TEST-600',
            'from_location_id' => $location->id,
            'to_location_id' => $f18PrefixId,
            'form_date' => '2026-05-15',
            'created_by' => $user->id,
        ]);

        $f18Detail = FormF18Detail::create([
            'header_id' => $f18Header->id,
            'business_id' => $business->id,
            'product_id' => $product->id,
            'from_location_id' => $location->id,
            'to_location_id' => $f18PrefixId,
            'qty' => 10,
            'issued_purchase_unit_price' => 100.00,
            'issued_purchase_total' => 1000.00,
            'issued_sale_unit_price' => 200.00,
            'issued_sale_total' => 2000.00,
            'received_purchase_unit_price' => 100.00,
            'received_purchase_total' => 1000.00,
            'received_sale_unit_price' => 200.00,
            'received_sale_total' => 2000.00,
        ]);

        // 6. Create Petro Settlement Credit Sale Payment
        $settlementId = DB::table('settlements')->insertGetId([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'settlement_no' => 'SET-999',
            'transaction_date' => '2026-05-15',
        ]);

        DB::table('settlement_credit_sale_payments')->insert([
            'settlement_no' => $settlementId,
            'business_id' => $business->id,
            'customer_id' => 1,
            'product_id' => $product->id,
            'order_number' => 'ORD-1',
            'order_date' => '2026-05-15',
            'price' => 150.00,
            'discount' => 0.0,
            'total_discount' => 0.0,
            'sub_total' => 450.00,
            'qty' => 3,
            'amount' => 450.00,
        ]);

        // 7. Create standard Lubricant sales (Total sales = 1500)
        $sellTrans = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-15 11:00:00',
            'total_before_tax' => 1500.00,
            'final_total' => 1500.00,
            'invoice_no' => 'INV-SELL-1',
            'created_by' => $user->id,
        ]);

        DB::table('transaction_sell_lines')->insert([
            'transaction_id' => $sellTrans->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 10,
            'unit_price' => 150.00,
            'unit_price_inc_tax' => 150.00,
        ]);

        // 8. Fetch F15 AJAX and Assert
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/get-15-setting-data?location_id=' . $location->id . '&start_date=2026-05-15');

        $response->assertStatus(200);

        // Assert Direct Purchase is F16A only (1500.00)
        $expectedF16AFormatted = number_format(1500.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedF16AFormatted, $response->json('direct_purchase_today'));
        $this->assertStringContainsString('F16A/777', $response->json('direct_purchase_book_no'));

        // Assert Store Purchase is F18 only (2000.00)
        $expectedF18Formatted = number_format(2000.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedF18Formatted, $response->json('purchase_today'));
        $this->assertStringContainsString('F18/F18-TEST-600', $response->json('store_purchase_book_no'));

        // Assert Sub Total is F16A + F18 (3500.00)
        $expectedSubFormatted = number_format(3500.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedSubFormatted, $response->json('sub_total_today'));

        // Assert Credit Sale is 450.00
        $expectedCreditFormatted = number_format(450.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedCreditFormatted, $response->json('credit_today'));

        // Assert Cash Sale is 1500.00 (Total) - 450.00 (Credit) - 0 (Card) = 1050.00
        $expectedCashFormatted = number_format(1050.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedCashFormatted, $response->json('cash_today'));
    }
}
