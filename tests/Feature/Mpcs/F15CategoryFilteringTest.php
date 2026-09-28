<?php

namespace Tests\Feature\Mpcs;

use Tests\TestCase;
use App\User;
use App\Category;
use App\Product;
use App\Transaction;
use App\BusinessLocation;
use Modules\MPCS\Services\FormHelper;
use Modules\MPCS\Entities\MpcsF15CategorySelection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class F15CategoryFilteringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_f15_category_filtering_logic()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        // 1. Create Categories
        $fuelParent = Category::create([
            'name' => 'Fuel',
            'business_id' => $businessId,
            'parent_id' => 0,
            'created_by' => $user->id,
        ]);
        $fuelSub = Category::create([
            'name' => 'Sub-LP92',
            'business_id' => $businessId,
            'parent_id' => $fuelParent->id,
            'created_by' => $user->id,
        ]);

        $lubricantParent = Category::create([
            'name' => 'Lubricant',
            'business_id' => $businessId,
            'parent_id' => 0,
            'created_by' => $user->id,
        ]);
        $lubricantSub = Category::create([
            'name' => 'Sub-Loose',
            'business_id' => $businessId,
            'parent_id' => $lubricantParent->id,
            'created_by' => $user->id,
        ]);

        // 2. Create Products
        $fuelProduct = Product::create([
            'name' => 'Petrol 92',
            'business_id' => $businessId,
            'category_id' => $fuelParent->id,
            'sub_category_id' => $fuelSub->id,
            'sku' => 'FUEL-001',
            'type' => 'single',
            'unit_id' => 1,
        ]);
        // Lubricant product assigned directly to subcategory in category_id (to test subcategory resolution)
        $lubricantProduct = Product::create([
            'name' => '4T Loose Oil',
            'business_id' => $businessId,
            'category_id' => $lubricantSub->id,
            'sku' => 'LUB-001',
            'type' => 'single',
            'unit_id' => 1,
        ]);

        $v1 = DB::table('variations')->insertGetId([
            'product_id' => $fuelProduct->id,
            'name' => 'DUMMY1',
            'sub_sku' => 'FUEL-001',
            'default_purchase_price' => 100.0,
            'dpp_inc_tax' => 100.0,
            'profit_percent' => 0,
            'default_sell_price' => 120.0,
            'sell_price_inc_tax' => 120.0,
        ]);

        $v2 = DB::table('variations')->insertGetId([
            'product_id' => $lubricantProduct->id,
            'name' => 'DUMMY2',
            'sub_sku' => 'LUB-001',
            'default_purchase_price' => 200.0,
            'dpp_inc_tax' => 200.0,
            'profit_percent' => 0,
            'default_sell_price' => 250.0,
            'sell_price_inc_tax' => 250.0,
        ]);

        // Create a location
        $location = BusinessLocation::firstOrCreate(
            ['business_id' => $businessId],
            ['name' => 'Test Location', 'location_id' => 'TL01']
        );

        // 3. Create active category selection for ONLY Fuel parent (ID)
        MpcsF15CategorySelection::create([
            'business_id' => $businessId,
            'category_ids' => [$fuelParent->id],
            'created_by' => $user->id,
        ]);

        // Create transaction to test purchases
        $purchase = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-26 10:00:00',
            'total_before_tax' => 300.0,
            'final_total' => 300.0,
            'ref_no' => 'PUR-001',
            'created_by' => $user->id,
        ]);

        DB::table('purchase_lines')->insert([
            [
                'transaction_id' => $purchase->id,
                'product_id' => $fuelProduct->id,
                'variation_id' => $v1,
                'quantity' => 1,
                'purchase_price' => 100.0,
                'purchase_price_inc_tax' => 100.0,
            ],
            [
                'transaction_id' => $purchase->id,
                'product_id' => $lubricantProduct->id,
                'variation_id' => $v2,
                'quantity' => 1,
                'purchase_price' => 200.0,
                'purchase_price_inc_tax' => 200.0,
            ]
        ]);

        // Create cash sale for Fuel (should be included when Fuel is selected)
        $sell = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-26 10:30:00',
            'total_before_tax' => 120.0,
            'final_total' => 120.0,
            'invoice_no' => 'INV-001',
            'created_by' => $user->id,
        ]);

        DB::table('transaction_sell_lines')->insert([
            'transaction_id' => $sell->id,
            'product_id' => $fuelProduct->id,
            'variation_id' => $v1,
            'quantity' => 1,
            'unit_price' => 120.0,
            'unit_price_inc_tax' => 120.0,
        ]);

        DB::table('transaction_payments')->insert([
            'transaction_id' => $sell->id,
            'business_id' => $businessId,
            'amount' => 120.0,
            'method' => 'cash',
            'paid_on' => '2026-05-26 10:30:00',
        ]);

        // Test getPurchaseAsToDay with correct time-inclusive range
        $purchaseTotal = FormHelper::getPurchaseAsToDay($businessId, '2026-05-26 00:00:00', '2026-05-26 23:59:59');
        $this->assertEquals(100.0, (float)$purchaseTotal);

        // Test getF16aSaleTotalForDateRange
        // First we need to create form_f16_details entry since the query joins form_f16_details
        DB::table('form_f16_details')->insert([
            'transaction_id' => $purchase->id,
            'form_no' => 'F16-001',
            'created_at' => '2026-05-26 10:00:00',
        ]);
        $f16aSaleTotal = FormHelper::getF16aSaleTotalForDateRange($businessId, '2026-05-26', '2026-05-27', $location->id);
        $this->assertEquals(120.0, (float)$f16aSaleTotal); // sell_price_at_purchase/sell_price_inc_tax * quantity = 120.0 for Fuel Product

        // Test getCashTodayExcludingFuel
        // Normally, Fuel is excluded by name. But since Fuel is selected, it should be included!
        $cashTotal = FormHelper::getCashTodayExcludingFuel($businessId, '2026-05-26', $location->id);
        $this->assertEquals(120.0, (float)$cashTotal);
    }
}
