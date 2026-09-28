<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Product;
use App\Variation;
use App\Transaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class F16ATest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_f16a_unit_sale_price_uses_variation_table_price()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business');
        }
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // 1. Create a Product
        $product = Product::create([
            'name' => 'Lanka Petrol 92 Test',
            'business_id' => $business->id,
            'type' => 'single',
            'sku' => 'LPT-SKU-99',
            'tax' => 1,
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);

        // Create a variation in variations table with selling price 500
        $variation = Variation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'sub_sku' => 'LPT-SKU-99',
            'default_purchase_price' => 300.00,
            'dpp_inc_tax' => 300.00,
            'default_sell_price' => 500.00,
            'sell_price_inc_tax' => 500.00,
        ]);

        // Insert a record into variation_prices with a completely different price (e.g. 330) and product_id
        // to simulate the database mismatch where the primary key of variation_prices does not match variations
        $otherProduct = Product::create([
            'name' => 'Other Product Test',
            'business_id' => $business->id,
            'type' => 'single',
            'sku' => 'OPT-SKU-99',
            'tax' => 1,
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);

        // Delete any existing row with this ID to avoid duplicate key error
        DB::table('variation_prices')->where('id', $variation->id)->delete();

        // Insert with id matching the variation's id, but product_id pointing to the other product and price 330
        DB::table('variation_prices')->insert([
            'id' => $variation->id,
            'name' => 'DUMMY',
            'product_id' => $otherProduct->id,
            'sub_sku' => 'OPT-SKU-99',
            'product_variation_id' => $variation->product_variation_id ?? 1,
            'variation_value_id' => null,
            'default_purchase_price' => 300.00,
            'dpp_inc_tax' => 300.00,
            'default_sell_price' => 330.00,
            'sell_price_inc_tax' => 330.00,
        ]);

        // Create a purchase transaction
        $purchaseTrans = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 10:00:00',
            'total_before_tax' => 300.00,
            'final_total' => 300.00,
            'ref_no' => 'INV-F16A-TEST-99',
            'created_by' => $user->id,
        ]);

        DB::table('purchase_lines')->insert([
            'transaction_id' => $purchaseTrans->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 1,
            'purchase_price' => 300.00,
            'purchase_price_inc_tax' => 300.00,
            'sell_price_at_purchase' => 410.00, // Historical unit sale price
        ]);

        // Hit F16A ajax endpoint
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/get-form-16a?location_id=' . $location->id . '&start_date=2026-06-01', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        // Find the row for our product
        $testRow = collect($data)->first(function ($row) {
            return strpos($row['product'], 'Lanka Petrol 92 Test') !== false;
        });

        $this->assertNotNull($testRow);
        
        // Assert that the unit sale price is 410.00 (from sell_price_at_purchase), not 500.00 (current variations table)
        $this->assertEquals(410.00, (float) $testRow['unit_sale_price']);
    }
}
