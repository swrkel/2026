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

class F15PriceChangeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f15_price_change_logic_flow()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        // Create Lubricant category
        $lubricantParent = Category::create([
            'name' => 'Lubricant',
            'business_id' => $businessId,
            'parent_id' => 0,
            'created_by' => $user->id,
        ]);

        // Create product
        $product = Product::create([
            'name' => 'Test Lubricant',
            'business_id' => $businessId,
            'category_id' => $lubricantParent->id,
            'sku' => 'TEST-LUB-999',
            'type' => 'single',
            'unit_id' => 1,
        ]);

        // Save category selection
        MpcsF15CategorySelection::create([
            'business_id' => $businessId,
            'category_ids' => [$lubricantParent->id],
            'created_by' => $user->id,
        ]);

        // 1. Create a Price Change (F17) on 2026-05-10
        $f17 = FormF17Header::create([
            'business_id' => $businessId,
            'date' => '2026-05-10',
            'form_no' => 999,
            'total_price_change_gain' => 150.00,
            'total_price_change_loss' => 50.00,
            'user' => $user->id,
        ]);

        FormF17Detail::create([
            'header_id' => $f17->id,
            'product_id' => $product->id,
            'select_mode' => 'increase',
            'price_changed_gain' => 150.00,
        ]);

        FormF17Detail::create([
            'header_id' => $f17->id,
            'product_id' => $product->id,
            'select_mode' => 'decrease',
            'price_changed_loss' => 50.00,
        ]);

        // 2. Fetch F15 values on Day X (2026-05-10)
        // Expected: Today has the values, Previous is 0.00
        $response = $this->actingAs($user)->getJson('/mpcs/get-15-setting-data?start_date=2026-05-10');
        $response->assertStatus(200);
        $data = $response->json();

        $precision = DB::table('business')->where('id', $businessId)->value('currency_precision') ?? 2;
        $format = function($val) use ($precision) {
            return number_format($val, $precision, '.', ',');
        };

        $this->assertEquals($format(150.00), $data['price_increment_today']);
        $this->assertEquals($format(0.00), $data['price_increment_previous']);
        $this->assertEquals($format(150.00), $data['price_increment_total']);

        $this->assertEquals($format(50.00), $data['price_reduction_today']);
        $this->assertEquals($format(0.00), $data['price_reduction_previous']);
        $this->assertEquals($format(50.00), $data['price_reduction_total']);

        // 3. Fetch F15 values on Day X+1 (2026-05-11)
        // Expected: Today is 0.00, Previous carries forward 150.00 / 50.00
        $response2 = $this->actingAs($user)->getJson('/mpcs/get-15-setting-data?start_date=2026-05-11');
        $response2->assertStatus(200);
        $data2 = $response2->json();

        $this->assertEquals($format(0.00), $data2['price_increment_today']);
        $this->assertEquals($format(150.00), $data2['price_increment_previous']);
        $this->assertEquals($format(150.00), $data2['price_increment_total']);

        $this->assertEquals($format(0.00), $data2['price_reduction_today']);
        $this->assertEquals($format(50.00), $data2['price_reduction_previous']);
        $this->assertEquals($format(50.00), $data2['price_reduction_total']);

        // 4. Save F22 Form on Day X+2 (2026-05-12)
        $f22 = FormF22Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => 888,
            'form_date' => '2026-05-12',
            'created_by' => $user->id,
            'status' => 1,
        ]);

        // 5. Fetch F15 values on Day X+2 (2026-05-12)
        // Expected: Should NOT reset to zero (0.00) because F22 does not reset it anymore (carry forward)
        $response3 = $this->actingAs($user)->getJson('/mpcs/get-15-setting-data?start_date=2026-05-12');
        $response3->assertStatus(200);
        $data3 = $response3->json();

        $this->assertEquals($format(0.00), $data3['price_increment_today']);
        $this->assertEquals($format(150.00), $data3['price_increment_previous']);
        $this->assertEquals($format(150.00), $data3['price_increment_total']);

        $this->assertEquals($format(0.00), $data3['price_reduction_today']);
        $this->assertEquals($format(50.00), $data3['price_reduction_previous']);
        $this->assertEquals($format(50.00), $data3['price_reduction_total']);
    }
}
