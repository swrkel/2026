<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Category;
use App\Product;
use App\Variation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\MPCS\Entities\FormF18Header;
use Modules\MPCS\Entities\FormF18Detail;
use Modules\MPCS\Entities\MpcsF15CategorySelection;
use Modules\MPCS\Services\FormHelper;

class F18PurchaseToF15StorePurchaseTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_f18_purchase_is_included_in_f15_store_purchase_total_and_refs()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business');
        }
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // 1. Create Category
        $category = Category::create([
            'name' => 'F15 Active Category',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);

        $subCategory = Category::create([
            'name' => 'F15 Active SubCategory',
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

        // 3. Create a Product in that sub-category
        $product = Product::create([
            'name' => 'F18 Product',
            'business_id' => $business->id,
            'type' => 'single',
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'sku' => 'F18-SKU-99',
            'tax' => 1,
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);

        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => 'F18-SKU-99',
            'default_purchase_price' => 100.00,
            'dpp_inc_tax' => 100.00,
            'default_sell_price' => 150.00,
            'sell_price_inc_tax' => 150.00,
        ]);

        // 4. Create F18 prefix record
        $f18PrefixId = \DB::table('form_f18_prefix_numbers')->insertGetId([
            'business_id' => $business->id,
            'opening_date' => '2026-05-01',
            'prefix' => 'F18-TEST',
            'starting_number' => 500,
            'transferred_locations' => json_encode([$location->name]),
            'created_by' => $user->id,
        ]);

        // Create an F18 Header and Detail (received sale total = 1500.00)
        $f18Header = FormF18Header::create([
            'business_id' => $business->id,
            'form_no' => 'F18-TEST-500',
            'from_location_id' => $location->id,
            'to_location_id' => $f18PrefixId, // Synthetic key
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
            'issued_sale_unit_price' => 150.00,
            'issued_sale_total' => 1500.00,
            'received_purchase_unit_price' => 100.00,
            'received_purchase_total' => 1000.00,
            'received_sale_unit_price' => 150.00,
            'received_sale_total' => 1500.00,
        ]);

        // 5. Test FormHelper returns correct F18 received sale total
        $total = FormHelper::getF18ReceivedSaleTotalForDateRange($business->id, '2026-05-15', '2026-05-16');
        $this->assertEquals(1500.00, $total);

        // 6. Test F15 get-15-setting-data AJAX response contains F18 purchase total and book ref number
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/get-15-setting-data?location_id=' . $location->id . '&start_date=2026-05-15');

        $response->assertStatus(200);
        $expectedTotalFormatted = number_format(1500.00, $business->currency_precision, '.', ',');
        $this->assertEquals($expectedTotalFormatted, $response->json('purchase_today'));
        $this->assertStringContainsString('F18/F18-TEST-500', $response->json('form_16a_number'));
    }
}
