<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use App\Utils\ProductUtil;

class TankTransactionSummaryTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function summary_and_product_stock_details_include_dip_reset_quantity(): void
    {
        DB::table('business')->where('id', $this->businessId)->update(['start_date' => '2026-01-01']);

        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->orderBy('id')
            ->value('id');

        $tankNumber = 'TST-SUM-' . uniqid();
        $tankId = DB::table('fuel_tanks')->insertGetId([
            'business_id' => $this->businessId,
            'product_id' => $this->productId,
            'fuel_tank_number' => $tankNumber,
            'fuel_type' => 'Petrol',
            'location_id' => $locationId,
            'storage_volume' => 10000,
            'current_balance' => 1400,
            'transaction_date' => '2026-05-19',
            'created_at' => '2026-05-19 00:00:00',
            'updated_at' => '2026-05-19 00:00:00',
        ]);

        $openingTransactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'type' => 'opening_stock',
            'status' => 'received',
            'transaction_date' => '2026-05-19',
            'created_by' => $this->userId,
            'created_at' => '2026-05-19 08:00:00',
            'updated_at' => '2026-05-19 08:00:00',
        ]);

        DB::table('tank_purchase_lines')->insert([
            'business_id' => $this->businessId,
            'transaction_id' => $openingTransactionId,
            'tank_id' => $tankId,
            'product_id' => $this->productId,
            'quantity' => 350,
            'created_at' => '2026-05-19 08:00:00',
            'updated_at' => '2026-05-19 08:00:00',
        ]);

        $purchaseTransactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'type' => 'purchase',
            'status' => 'received',
            'transaction_date' => '2026-05-20',
            'created_by' => $this->userId,
            'created_at' => '2026-05-20 10:00:00',
            'updated_at' => '2026-05-20 10:00:00',
        ]);

        DB::table('tank_purchase_lines')->insert([
            'business_id' => $this->businessId,
            'transaction_id' => $purchaseTransactionId,
            'tank_id' => $tankId,
            'product_id' => $this->productId,
            'quantity' => 1150,
            'created_at' => '2026-05-20 10:00:00',
            'updated_at' => '2026-05-20 10:00:00',
        ]);

        $saleTransactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'type' => 'sell',
            'status' => 'final',
            'transaction_date' => '2026-05-20',
            'created_by' => $this->userId,
            'created_at' => '2026-05-20 11:00:00',
            'updated_at' => '2026-05-20 11:00:00',
        ]);

        DB::table('tank_sell_lines')->insert([
            'business_id' => $this->businessId,
            'transaction_id' => $saleTransactionId,
            'tank_id' => $tankId,
            'product_id' => $this->productId,
            'quantity' => 100,
            'created_at' => '2026-05-20 11:00:00',
            'updated_at' => '2026-05-20 11:00:00',
        ]);

        $resetTransactionId = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'type' => 'stock_adjustment',
            'sub_type' => 'dip_resetting',
            'stock_adjustment_type' => 'decrease',
            'status' => 'received',
            'transaction_date' => '2026-05-20',
            'created_by' => $this->userId,
            'created_at' => '2026-05-20 12:00:00',
            'updated_at' => '2026-05-20 12:00:00',
        ]);

        DB::table('tank_sell_lines')->insert([
            'business_id' => $this->businessId,
            'transaction_id' => $resetTransactionId,
            'tank_id' => $tankId,
            'product_id' => $this->productId,
            'quantity' => 50,
            'created_at' => '2026-05-20 12:00:00',
            'updated_at' => '2026-05-20 12:00:00',
        ]);

        DB::table('stock_adjustment_lines')->insert([
            'transaction_id' => $resetTransactionId,
            'product_id' => $this->productId,
            'variation_id' => DB::table('variations')->where('product_id', $this->productId)->value('id'),
            'quantity' => 50,
            'unit_price' => 1,
            'type' => 'decrease',
            'stock_adjustment_type' => 'decrease',
            'tank_id' => $tankId,
            'created_at' => '2026-05-20 12:00:00',
            'updated_at' => '2026-05-20 12:00:00',
        ]);

        $pumpId = DB::table('pumps')->insertGetId([
            'business_id' => $this->businessId,
            'pump_name' => 'TST Pump ' . uniqid(),
            'location_id' => $locationId,
            'fuel_type' => 'Fuel',
            'installation_date' => '2026-05-20',
            'pump_no' => 'TST-P-' . uniqid(),
            'product_id' => $this->productId,
            'fuel_tank_id' => $tankId,
            'qty' => 0,
            'transaction_date' => '2026-05-20',
            'created_at' => '2026-05-20 12:30:00',
            'updated_at' => '2026-05-20 12:30:00',
        ]);

        DB::table('meter_sales')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'qty' => 3,
            'testing_qty' => 3,
            'created_at' => '2026-05-20 12:30:00',
            'updated_at' => '2026-05-20 12:30:00',
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/tanks-transaction-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'fuel_tank_number' => $tankNumber,
            ]));

        $response->assertOk();
        $row = $response->json('data.0');

        $this->assertSame('350.000', $row['starting_qty']);
        $this->assertStringContainsString('1153', $row['purchase_qty']);
        $this->assertStringContainsString('150', $row['sold_qty']);
        $this->assertStringContainsString('1353', $row['balance_qty']);

        $stockDetails = app(ProductUtil::class)->getTankStockDetails($this->businessId, $this->productId, $locationId, $tankId);

        $this->assertSame(-50.0, (float) $stockDetails['total_adjusted']);
    }
}
