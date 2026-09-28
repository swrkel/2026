<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\ProductVariation;
use App\User;
use App\Variation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\Mpcs20FormSettings;
use Modules\Petro\Entities\Settlement;
use Tests\TestCase;

class F20CashQuantityDeductionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * F20 Form Cash: qty per product must equal Total Sale Qty minus Credit Sale Qty.
     *
     * Scenario (normal path – scsp.settlement_no = settlements.settlement_no string):
     *   - MeterSale for settlement: 100 qty × 10 = 1000
     *   - settlement_credit_sale_payments for same settlement: 30 qty
     *   - Expected Cash qty = 100 - 30 = 70
     */
    public function test_f20_cash_form_deducts_credit_sale_qty_from_total_sale_qty(): void
    {
        $this->withoutExceptionHandling();
        activity()->disableLogging();

        [$user, $business, $location, $product, $subCategory] = $this->createBaseFixtures();

        $date = '2099-06-01';

        $settlement = Settlement::create([
            'business_id'      => $business->id,
            'location_id'      => $location->id,
            'settlement_no'    => 'F20-SET-TEST-' . uniqid(),
            'transaction_date' => $date,
            'status'           => 'active',
            'created_by'       => $user->id,
        ]);

        // MeterSale: stored with settlements.id as FK
        DB::table('meter_sales')->insert([
            'business_id'   => $business->id,
            'settlement_no' => $settlement->id,
            'product_id'    => $product->id,
            'qty'           => 100,
            'price'         => 10,
            'sub_total'     => 1000,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Credit payment: settlement_no = string settlement number
        DB::table('settlement_credit_sale_payments')->insert([
            'business_id'   => $business->id,
            'settlement_no' => $settlement->settlement_no,
            'customer_id'   => 1,
            'product_id'    => $product->id,
            'qty'           => 30,
            'price'         => 10,
            'amount'        => 300,
            'order_date'    => $date,
            'order_number'  => 'F20-TEST-ORD-001',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $detail = $this->callEndpointAndGetDetail($user, $business, $settlement, $product, $date);

        $this->assertEquals(
            70.0,
            (float) $detail['qty'],
            'Cash qty must equal Total Sale Qty minus Credit Sale Qty: 100 - 30 = 70'
        );
    }

    /**
     * F20 Form Cash: deduction must also work when scsp.settlement_no stores the
     * numeric settlement ID (not the string settlement_no).
     *
     * This is the failing case: the JOIN in creditBaseQuery resolves
     * COALESCE(st.settlement_no, scsp.settlement_no) = st.settlement_no (string),
     * but ONLY if the JOIN succeeds. When it fails, scsp.settlement_no (numeric)
     * is used as the key, which will NOT match the totalSales settlement_no (string).
     */
    public function test_f20_cash_deducts_credit_when_scsp_stores_numeric_settlement_id(): void
    {
        $this->withoutExceptionHandling();
        activity()->disableLogging();

        [$user, $business, $location, $product, $subCategory] = $this->createBaseFixtures();

        $date = '2099-06-02';

        $settlement = Settlement::create([
            'business_id'      => $business->id,
            'location_id'      => $location->id,
            'settlement_no'    => 'F20-NUM-TEST-' . uniqid(),
            'transaction_date' => $date,
            'status'           => 'active',
            'created_by'       => $user->id,
        ]);

        // MeterSale: uses settlements.id (numeric) as FK
        DB::table('meter_sales')->insert([
            'business_id'   => $business->id,
            'settlement_no' => $settlement->id,
            'product_id'    => $product->id,
            'qty'           => 100,
            'price'         => 10,
            'sub_total'     => 1000,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Credit payment: settlement_no = NUMERIC ID stored as string (alternate format)
        // In this case the JOIN in creditBaseQuery resolves via:
        //   CONVERT(scsp.settlement_no) = CAST(st.id AS CHAR)  ← matches
        // So COALESCE(st.settlement_no, scsp.settlement_no) = st.settlement_no (string)
        // And the lookup key must match totalSales settlement_no (also string).
        DB::table('settlement_credit_sale_payments')->insert([
            'business_id'   => $business->id,
            'settlement_no' => (string) $settlement->id,  // numeric ID stored as string
            'customer_id'   => 1,
            'product_id'    => $product->id,
            'qty'           => 30,
            'price'         => 10,
            'amount'        => 300,
            'order_date'    => $date,
            'order_number'  => 'F20-NUM-ORD-001',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $detail = $this->callEndpointAndGetDetail($user, $business, $settlement, $product, $date);

        $this->assertEquals(
            70.0,
            (float) $detail['qty'],
            'Cash qty must equal 100 - 30 = 70 even when scsp.settlement_no is a numeric ID'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createBaseFixtures(): array
    {
        $user = User::firstOrCreate(
            ['username' => 'f20-cash-qty-test'],
            [
                'first_name' => 'F20',
                'last_name'  => 'Tester',
                'email'      => 'f20-cash-qty-test@example.test',
                'password'   => bcrypt('password'),
            ]
        );

        $business = Business::firstOrCreate(
            ['name' => 'F20 Cash Qty Test Business'],
            [
                'currency_id'            => 1,
                'owner_id'               => $user->id,
                'stop_selling_before'    => 0,
                'auto_repair_settings'   => '',
                'asset_settings'         => '',
                'font_size'              => 12,
                'font_family'            => 'Arial',
                'weighing_scale_setting' => '',
            ]
        );

        $user->business_id = $business->id;
        $user->save();

        $location = BusinessLocation::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'F20 Cash Qty Test Location'],
            [
                'country'                  => 'Test',
                'state'                    => 'Test',
                'city'                     => 'Test',
                'zip_code'                 => '00000',
                'invoice_scheme_id'        => 1,
                'invoice_layout_id'        => 1,
                'default_payment_accounts' => '{}',
            ]
        );

        $parentCategory = Category::create([
            'name'               => 'F20 Test Parent',
            'business_id'        => $business->id,
            'parent_id'          => 0,
            'created_by'         => $user->id,
            'vat_not_applicable' => 0,
        ]);

        $subCategory = Category::create([
            'name'               => 'F20 Test Sub',
            'business_id'        => $business->id,
            'parent_id'          => $parentCategory->id,
            'created_by'         => $user->id,
            'vat_not_applicable' => 0,
        ]);

        $product = Product::create([
            'name'            => 'F20 Test Fuel',
            'business_id'     => $business->id,
            'unit_id'         => 1,
            'sku'             => 'F20-FUEL-' . uniqid(),
            'type'            => 'single',
            'tax_type'        => 'inclusive',
            'category_id'     => $parentCategory->id,
            'sub_category_id' => $subCategory->id,
            'created_by'      => $user->id,
        ]);

        $productVariation = ProductVariation::create([
            'name'       => 'DUMMY',
            'product_id' => $product->id,
            'is_dummy'   => 1,
        ]);

        Variation::create([
            'product_id'             => $product->id,
            'name'                   => 'DUMMY',
            'sub_sku'                => $product->sku,
            'product_variation_id'   => $productVariation->id,
            'default_purchase_price' => 10,
            'dpp_inc_tax'            => 10,
            'profit_percent'         => 0,
            'default_sell_price'     => 10,
            'sell_price_inc_tax'     => 10,
        ]);

        Mpcs20FormSettings::create([
            'business_id'     => $business->id,
            'opening_date'    => '2020-01-01',
            'starting_number' => 1,
            'cash_sale'       => 1,
            'credit_sale'     => 1,
            'total_sale'      => 1,
            'category'        => (string) $subCategory->id,
            'product'         => (string) $product->id,
        ]);

        return [$user, $business, $location, $product, $subCategory];
    }

    private function callEndpointAndGetDetail(
        $user,
        $business,
        $settlement,
        $product,
        string $date
    ): array {
        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $business->id,
                'user.business_id' => $business->id,
            ])
            ->getJson('/mpcs/get-form-20-datas?form_type=Cash&form_date_range=' . $date);

        $response->assertStatus(200);

        $products = $response->json('products');
        $this->assertNotEmpty($products, 'products array must not be empty');

        $entry = collect($products)->first(function ($row) use ($settlement) {
            return $row['settlement_no'] === $settlement->settlement_no;
        });

        $this->assertNotNull($entry, 'Settlement entry not found in Cash form response');

        $detail = collect($entry['details'])->first(function ($d) use ($product) {
            return (int) $d['product_id'] === $product->id;
        });

        $this->assertNotNull($detail, 'Product detail not found in settlement entry');

        return $detail;
    }
}
