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
use Modules\MPCS\Services\FormHelper;
use Illuminate\Support\Facades\DB;

class F15LubricantGasFilterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_f15_filters_lubricant_and_gas_categories_only()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business');
        }
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // 1. Create 3 Parent Categories:
        // A. Non-lubricant/non-gas (e.g. Fuel)
        $fuelCategory = Category::create([
            'name' => 'Standard Fuel Category',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);
        // B. Lubricant
        $lubricantCategory = Category::create([
            'name' => 'Super Lubricants Category',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);
        // C. Gas
        $gasCategory = Category::create([
            'name' => 'LPG Gas Category',
            'business_id' => $business->id,
            'parent_id' => 0,
            'created_by' => $user->id
        ]);

        // 2. Create products for each
        // Product A: Fuel (Non Lubricant/Gas)
        $fuelProduct = Product::create([
            'name' => 'Petrol 95 Fuel',
            'business_id' => $business->id,
            'type' => 'single',
            'category_id' => $fuelCategory->id,
            'sku' => 'SKU-FUEL-TEST',
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);
        $fuelVar = Variation::create([
            'product_id' => $fuelProduct->id,
            'name' => 'DUMMY',
            'sub_sku' => 'SKU-FUEL-TEST',
            'default_purchase_price' => 100.00,
            'dpp_inc_tax' => 100.00,
            'default_sell_price' => 150.00,
            'sell_price_inc_tax' => 150.00,
        ]);

        // Product B: Lubricant
        $lubProduct = Product::create([
            'name' => 'Servo 4T Lube',
            'business_id' => $business->id,
            'type' => 'single',
            'category_id' => $lubricantCategory->id,
            'sku' => 'SKU-LUB-TEST',
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);
        $lubVar = Variation::create([
            'product_id' => $lubProduct->id,
            'name' => 'DUMMY',
            'sub_sku' => 'SKU-LUB-TEST',
            'default_purchase_price' => 200.00,
            'dpp_inc_tax' => 200.00,
            'default_sell_price' => 300.00,
            'sell_price_inc_tax' => 300.00,
        ]);

        // Product C: Gas
        $gasProduct = Product::create([
            'name' => 'LPG Cylinder Gas',
            'business_id' => $business->id,
            'type' => 'single',
            'category_id' => $gasCategory->id,
            'sku' => 'SKU-GAS-TEST',
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);
        $gasVar = Variation::create([
            'product_id' => $gasProduct->id,
            'name' => 'DUMMY',
            'sub_sku' => 'SKU-GAS-TEST',
            'default_purchase_price' => 400.00,
            'dpp_inc_tax' => 400.00,
            'default_sell_price' => 600.00,
            'sell_price_inc_tax' => 600.00,
        ]);

        // 3. Direct Purchase (F16A) for each product
        // F16A Purchase A (Fuel): amount = 150.00 (sell price) * 10 = 1500.00
        $purchaseFuel = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 10:00:00',
            'total_before_tax' => 1000.00,
            'final_total' => 1000.00,
            'ref_no' => 'PUR-F16A-FUEL',
            'created_by' => $user->id,
        ]);
        DB::table('purchase_lines')->insert([
            'transaction_id' => $purchaseFuel->id,
            'product_id' => $fuelProduct->id,
            'variation_id' => $fuelVar->id,
            'quantity' => 10,
            'purchase_price' => 100.00,
            'purchase_price_inc_tax' => 100.00,
            'sell_price_at_purchase' => 150.00
        ]);
        DB::table('form_f16_details')->insert([
            'transaction_id' => $purchaseFuel->id,
            'form_no' => 101,
            'invoice_no' => 'INV-FUEL',
            'supplier' => 'Fuel Supplier'
        ]);

        // F16A Purchase B (Lubricant): amount = 300.00 * 10 = 3000.00
        $purchaseLub = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 10:00:00',
            'total_before_tax' => 2000.00,
            'final_total' => 2000.00,
            'ref_no' => 'PUR-F16A-LUB',
            'created_by' => $user->id,
        ]);
        DB::table('purchase_lines')->insert([
            'transaction_id' => $purchaseLub->id,
            'product_id' => $lubProduct->id,
            'variation_id' => $lubVar->id,
            'quantity' => 10,
            'purchase_price' => 200.00,
            'purchase_price_inc_tax' => 200.00,
            'sell_price_at_purchase' => 300.00
        ]);
        DB::table('form_f16_details')->insert([
            'transaction_id' => $purchaseLub->id,
            'form_no' => 102,
            'invoice_no' => 'INV-LUB',
            'supplier' => 'Lube Supplier'
        ]);

        // F16A Purchase C (Gas): amount = 600.00 * 10 = 6000.00
        $purchaseGas = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 10:00:00',
            'total_before_tax' => 4000.00,
            'final_total' => 4000.00,
            'ref_no' => 'PUR-F16A-GAS',
            'created_by' => $user->id,
        ]);
        DB::table('purchase_lines')->insert([
            'transaction_id' => $purchaseGas->id,
            'product_id' => $gasProduct->id,
            'variation_id' => $gasVar->id,
            'quantity' => 10,
            'purchase_price' => 400.00,
            'purchase_price_inc_tax' => 400.00,
            'sell_price_at_purchase' => 600.00
        ]);
        DB::table('form_f16_details')->insert([
            'transaction_id' => $purchaseGas->id,
            'form_no' => 103,
            'invoice_no' => 'INV-GAS',
            'supplier' => 'Gas Supplier'
        ]);

        // Verify direct purchases for Lubricant + Gas only = 3000 + 6000 = 9000.00
        $f16aTotal = FormHelper::getF16aSaleTotalForDateRange($business->id, '2026-06-01', '2026-06-02', $location->id);
        $this->assertEquals(9000.00, $f16aTotal);

        // 4. Petro Credit Sales
        // A. Credit sale for Fuel: amount = 450.00
        $settlement = DB::table('settlements')->insertGetId([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'settlement_no' => 'SET-CR-TEST',
            'transaction_date' => '2026-06-01',
        ]);
        DB::table('settlement_credit_sale_payments')->insert([
            'settlement_no' => $settlement,
            'business_id' => $business->id,
            'customer_id' => 1,
            'product_id' => $fuelProduct->id,
            'order_number' => 'ORD-FUEL',
            'order_date' => '2026-06-01',
            'price' => 150.00,
            'qty' => 3,
            'amount' => 450.00,
        ]);

        // B. Credit sale for Lubricant: amount = 900.00
        DB::table('settlement_credit_sale_payments')->insert([
            'settlement_no' => $settlement,
            'business_id' => $business->id,
            'customer_id' => 1,
            'product_id' => $lubProduct->id,
            'order_number' => 'ORD-LUB',
            'order_date' => '2026-06-01',
            'price' => 300.00,
            'qty' => 3,
            'amount' => 900.00,
        ]);

        // C. Credit sale for Gas: amount = 1800.00
        DB::table('settlement_credit_sale_payments')->insert([
            'settlement_no' => $settlement,
            'business_id' => $business->id,
            'customer_id' => 1,
            'product_id' => $gasProduct->id,
            'order_number' => 'ORD-GAS',
            'order_date' => '2026-06-01',
            'price' => 600.00,
            'qty' => 3,
            'amount' => 1800.00,
        ]);

        // Verify credit sales for Lubricant + Gas only = 900 + 1800 = 2700.00
        $creditTotal = FormHelper::getCreditAsToDay($business->id, '2026-06-01', '2026-06-01', $location->id);
        $this->assertEquals(2700.00, $creditTotal);

        // 5. Card and Cash sales via transactions
        // Standard sales transaction for Fuel: amount = 1500.00
        $sellFuel = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 11:00:00',
            'total_before_tax' => 1500.00,
            'final_total' => 1500.00,
            'invoice_no' => 'INV-SELL-FUEL',
            'created_by' => $user->id,
        ]);
        DB::table('transaction_sell_lines')->insert([
            'transaction_id' => $sellFuel->id,
            'product_id' => $fuelProduct->id,
            'variation_id' => $fuelVar->id,
            'quantity' => 10,
            'unit_price' => 150.00,
            'unit_price_inc_tax' => 150.00,
        ]);

        // Standard sales transaction for Lubricant: amount = 3000.00
        $sellLub = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 11:00:00',
            'total_before_tax' => 3000.00,
            'final_total' => 3000.00,
            'invoice_no' => 'INV-SELL-LUB',
            'created_by' => $user->id,
        ]);
        DB::table('transaction_sell_lines')->insert([
            'transaction_id' => $sellLub->id,
            'product_id' => $lubProduct->id,
            'variation_id' => $lubVar->id,
            'quantity' => 10,
            'unit_price' => 300.00,
            'unit_price_inc_tax' => 300.00,
        ]);

        // Standard sales transaction for Gas: amount = 6000.00
        $sellGas = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-06-01 11:00:00',
            'total_before_tax' => 6000.00,
            'final_total' => 6000.00,
            'invoice_no' => 'INV-SELL-GAS',
            'created_by' => $user->id,
        ]);
        DB::table('transaction_sell_lines')->insert([
            'transaction_id' => $sellGas->id,
            'product_id' => $gasProduct->id,
            'variation_id' => $gasVar->id,
            'quantity' => 10,
            'unit_price' => 600.00,
            'unit_price_inc_tax' => 600.00,
        ]);

        // Add Card payment for Lubricant sale = 1000.00
        DB::table('transaction_payments')->insert([
            'transaction_id' => $sellLub->id,
            'business_id' => $business->id,
            'amount' => 1000.00,
            'method' => 'card',
            'paid_on' => '2026-06-01 11:00:00',
        ]);

        // Add Card payment for Gas sale = 2000.00
        DB::table('transaction_payments')->insert([
            'transaction_id' => $sellGas->id,
            'business_id' => $business->id,
            'amount' => 2000.00,
            'method' => 'card',
            'paid_on' => '2026-06-01 11:00:00',
        ]);

        // Verify card sales for Lubricant + Gas only = 1000 + 2000 = 3000.00
        $cardTotal = FormHelper::getCardAsToDay($business->id, '2026-06-01', '2026-06-01', $location->id);
        $this->assertEquals(3000.00, $cardTotal);

        // Verify total sales for Lubricant + Gas only = 3000 (Lube) + 6000 (Gas) = 9000.00
        $salesTotal = FormHelper::getF15TotalSalesForDateRange($business->id, '2026-06-01', '2026-06-01', $location->id);
        $this->assertEquals(9000.00, $salesTotal);

        // Verify cash sales for Lubricant + Gas only:
        // Cash = Total Sales (9000) - Credit (2700) - Card (3000) = 3300.00
        $cashTotal = FormHelper::getCashAsToDay($business->id, '2026-06-01', '2026-06-01', $location->id);
        $this->assertEquals(3300.00, $cashTotal);
    }
}
