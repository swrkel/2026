<?php

namespace Tests\Feature\Mpcs;

use Tests\TestCase;
use App\User;
use App\Category;
use App\Product;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\MpcsF15CategorySelection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class F15PriceChangeCarryForwardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f15_price_change_carry_forward_and_category_filtering()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        // 1. Create Categories (Lubricant and Fuel)
        $lubricantParent = Category::create([
            'name' => 'Lubricant',
            'business_id' => $businessId,
            'parent_id' => 0,
            'created_by' => $user->id,
        ]);
        $lubricantSub = Category::create([
            'name' => 'Sub-Lubricant-Test',
            'business_id' => $businessId,
            'parent_id' => $lubricantParent->id,
            'created_by' => $user->id,
        ]);

        $fuelParent = Category::create([
            'name' => 'Fuel',
            'business_id' => $businessId,
            'parent_id' => 0,
            'created_by' => $user->id,
        ]);
        $fuelSub = Category::create([
            'name' => 'Sub-Fuel-Test',
            'business_id' => $businessId,
            'parent_id' => $fuelParent->id,
            'created_by' => $user->id,
        ]);

        // 2. Create Products
        $lubProduct = Product::create([
            'name' => 'Test Lubricant',
            'business_id' => $businessId,
            'category_id' => $lubricantParent->id,
            'sub_category_id' => $lubricantSub->id,
            'sku' => 'TEST-LUB-001',
            'type' => 'single',
            'unit_id' => 1,
        ]);

        $fuelProduct = Product::create([
            'name' => 'Test Fuel',
            'business_id' => $businessId,
            'category_id' => $fuelParent->id,
            'sub_category_id' => $fuelSub->id,
            'sku' => 'TEST-FUEL-001',
            'type' => 'single',
            'unit_id' => 1,
        ]);

        // 3. Set up active category selection (both Lubricant and Fuel)
        MpcsF15CategorySelection::create([
            'business_id' => $businessId,
            'category_ids' => [$lubricantParent->id, $fuelParent->id],
            'created_by' => $user->id,
        ]);

        // 4. Create a Price Change (F17) on 2026-05-10
        $f17 = FormF17Header::create([
            'business_id' => $businessId,
            'date' => '2026-05-10',
            'form_no' => 999,
            'total_price_change_gain' => 250.00,
            'total_price_change_loss' => 0.00,
            'user' => $user->id,
        ]);

        // Increase Lubricant by 150
        FormF17Detail::create([
            'header_id' => $f17->id,
            'product_id' => $lubProduct->id,
            'select_mode' => 'increase',
            'price_changed_gain' => 150.00,
        ]);

        // Increase Fuel by 100
        FormF17Detail::create([
            'header_id' => $f17->id,
            'product_id' => $fuelProduct->id,
            'select_mode' => 'increase',
            'price_changed_gain' => 100.00,
        ]);

        // 5. Fetch F15 values on 2026-05-10
        // Expected: Only Lubricant price change (150.00) is shown. Fuel (100.00) is ignored.
        $response = $this->actingAs($user)->getJson('/mpcs/get-15-setting-data?start_date=2026-05-10');
        $response->assertStatus(200);
        $data = $response->json();

        $precision = DB::table('business')->where('id', $businessId)->value('currency_precision') ?? 2;
        $format = function($val) use ($precision) {
            return number_format($val, $precision, '.', ',');
        };

        // Assert only Lubricant is included
        $this->assertEquals($format(150.00), $data['price_increment_today']);

        // 6. Create F22 Stock-Taking on 2026-05-12
        FormF22Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => 888,
            'form_date' => '2026-05-12',
            'created_by' => $user->id,
            'status' => 1,
        ]);

        // 7. Fetch F15 values on 2026-05-13 (after F22 date)
        // Expected: Price change carries forward (NOT reset by F22)
        $response2 = $this->actingAs($user)->getJson('/mpcs/get-15-setting-data?start_date=2026-05-13');
        $response2->assertStatus(200);
        $data2 = $response2->json();

        $this->assertEquals($format(150.00), $data2['price_increment_previous']);
    }
}
