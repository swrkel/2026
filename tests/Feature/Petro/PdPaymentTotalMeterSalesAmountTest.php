<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Modules\Petro\Http\Controllers\AddPaymentController;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\OtherIncome;

class PdPaymentTotalMeterSalesAmountTest extends PetroTestCase
{
    use DatabaseTransactions;

    /**
     * Reproduces the production scenario:
     *  - pump_operator_meter_sales has source='closing' and null p_o_payment_id
     *  - AddPaymentController correctly includes these closing records even if settlement mismatch.
     *
     * @test
     */
    public function test_total_amount_includes_meter_sale_with_source_closing_and_settlement_mismatch(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId      = $this->seedClosedPetroShift();
        // "Regular" settlement (ST-style), this is what the modal actually loads.
        $regularSettlementNo = 'ST-TEST-' . uniqid();
        $settlementId        = $this->seedPdSettlement((int) $pump->id, $shiftId, $regularSettlementNo);

        // Meter sale with source='closing' linked to the regular settlement (matches production id=1).
        $this->seedPumpOperatorMeterSaleRaw($shiftId, $regularSettlementNo, 84812.50, 'closing');

        // Other sale worth 5500.00 linked to settlement id.
        OtherSale::create([
            'business_id'     => $this->businessId,
            'settlement_no'   => $settlementId,
            'store_id'        => 1,
            'product_id'      => $this->productId,
            'price'           => 5500.00,
            'qty'             => 1,
            'balance_stock'   => 0,
            'discount'        => 0,
            'discount_type'   => 'percentage',
            'discount_amount' => 0,
            'sub_total'       => 5500.00,
        ]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no'    => $regularSettlementNo,
            'type'             => 'settlement_pd',
            'source'           => 'petropd',
            'shift_ids'        => [$shiftId],
            'pump_operator_id' => $this->pumpOperatorId,
            'operator_id'      => $this->pumpOperatorId,
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view        = app(AddPaymentController::class)->create($request);
        $totalAmount = $view->getData()['total_amount'];

        // 84812.50 (meter) + 5500 (other) = 90312.50
        $this->assertEquals(90312.50, (float) $totalAmount);
    }

    /**
     * Covers the second production case:
     *  - pump_operator_meter_sales has source='closing', settlement_no='PDST1'   (ids 8,9)
     *  - The modal loads settlement with settlement_no='ST1'
     *  - Controller query uses settlement_no='ST1' → misses 'PDST1' records → total=0
     *
     * @test
     */
    public function test_total_amount_includes_meter_sales_when_settlement_no_mismatch_pdst_vs_st(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId       = $this->seedClosedPetroShift();
        // Settlement loaded by the modal (ST-style).
        $stSettlementNo   = 'ST-TEST-' . uniqid();
        $settlementId     = $this->seedPdSettlement((int) $pump->id, $shiftId, $stSettlementNo);

        // Meter sales with source='closing' but settlement_no='PDST-TEST' (different from what modal loads).
        $pdstSettlementNo = 'PDST-TEST-' . uniqid();
        $this->seedPumpOperatorMeterSaleRaw($shiftId, $pdstSettlementNo, 29500.00, 'closing');
        $this->seedPumpOperatorMeterSaleRaw($shiftId, $pdstSettlementNo, 55312.50, 'closing');

        // Other sale worth 5500.00.
        OtherSale::create([
            'business_id'     => $this->businessId,
            'settlement_no'   => $settlementId,
            'store_id'        => 1,
            'product_id'      => $this->productId,
            'price'           => 5500.00,
            'qty'             => 1,
            'balance_stock'   => 0,
            'discount'        => 0,
            'discount_type'   => 'percentage',
            'discount_amount' => 0,
            'sub_total'       => 5500.00,
        ]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no'    => $stSettlementNo,   // loads ST settlement, but meter sales say PDST
            'type'             => 'settlement_pd',
            'source'           => 'petropd',
            'shift_ids'        => [$shiftId],
            'pump_operator_id' => $this->pumpOperatorId,
            'operator_id'      => $this->pumpOperatorId,
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view        = app(AddPaymentController::class)->create($request);
        $totalAmount = $view->getData()['total_amount'];

        // 84812.50 (29500+55312.50 meter) + 5500 (other) = 90312.50
        $this->assertEquals(90312.50, (float) $totalAmount);
    }

    /**
     * Original passing test — shift_ids correct + meter sale source='closing'
     * matching settlement_no.
     *
     * @test
     */
    public function test_total_amount_includes_meter_sales_when_shift_ids_passed_as_array(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId      = $this->seedClosedPetroShift();
        $settlementNo = 'PDST-TEST-' . uniqid();
        $settlementId = $this->seedPdSettlement((int) $pump->id, $shiftId, $settlementNo);

        $this->seedPumpOperatorMeterSaleRaw($shiftId, $settlementNo, 84812.50, 'closing');

        OtherSale::create([
            'business_id'     => $this->businessId,
            'settlement_no'   => $settlementId,
            'store_id'        => 1,
            'product_id'      => $this->productId,
            'price'           => 5500.00,
            'qty'             => 1,
            'balance_stock'   => 0,
            'discount'        => 0,
            'discount_type'   => 'percentage',
            'discount_amount' => 0,
            'sub_total'       => 5500.00,
        ]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no'    => $settlementNo,
            'type'             => 'settlement_pd',
            'source'           => 'petropd',
            'shift_ids'        => [$shiftId],
            'pump_operator_id' => $this->pumpOperatorId,
            'operator_id'      => $this->pumpOperatorId,
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view        = app(AddPaymentController::class)->create($request);
        $totalAmount = $view->getData()['total_amount'];

        $this->assertEquals(90312.50, (float) $totalAmount);
    }

    /**
     * @test
     */
    public function test_total_amount_includes_meter_sales_even_when_p_o_payment_id_is_set(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId      = $this->seedClosedPetroShift();
        $settlementNo = 'PDST-TEST-' . uniqid();
        $settlementId = $this->seedPdSettlement((int) $pump->id, $shiftId, $settlementNo);

        $meterSaleId = $this->seedPumpOperatorMeterSaleRaw($shiftId, $settlementNo, 84812.50, 'closing');
        
        // Simulating the closed shift behavior where p_o_payment_id gets assigned a non-null ID.
        DB::table('pump_operator_meter_sales')->where('id', $meterSaleId)->update(['p_o_payment_id' => 999]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no'    => $settlementNo,
            'type'             => 'settlement_pd',
            'source'           => 'petropd',
            'shift_ids'        => [$shiftId],
            'pump_operator_id' => $this->pumpOperatorId,
            'operator_id'      => $this->pumpOperatorId,
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view        = app(AddPaymentController::class)->create($request);
        $totalAmount = $view->getData()['total_amount'];

        $this->assertEquals(84812.50, (float) $totalAmount);
    }


    private function seedClosedPetroShift(): int
    {
        $data = [
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 2,
            'shift_date'       => now(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        if (Schema::hasColumn('petro_shifts', 'work_shift_id')) {
            $data['work_shift_id'] = null;
        }

        $shiftId = DB::table('petro_shifts')->insertGetId($data);

        DB::table('pump_operator_assignments')->insert([
            'business_id'          => $this->businessId,
            'shift_id'             => $shiftId,
            'pump_operator_id'     => $this->pumpOperatorId,
            'status'               => 'close',
            'is_manually_closed'   => 1,
            'close_date_and_time'  => now(),
            'closed_in_settlement' => 0,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return $shiftId;
    }

    private function seedPdSettlement(int $pumpId, int $shiftId, string $settlementNo): int
    {
        $locationId = (int) DB::table('pumps')->where('id', $pumpId)->value('location_id');
        if (! $locationId) {
            $locationId = (int) DB::table('business_locations')->where('business_id', $this->businessId)->value('id');
        }

        return DB::table('settlements')->insertGetId([
            'settlement_no'      => $settlementNo,
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode([(string) $shiftId]),
            'note'               => null,
            'total_amount'       => 0,
            'status'             => 1,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    private function seedPumpOperatorMeterSaleRaw(int $shiftId, string $settlementNo, float $amount, string $source): int
    {
        $data = [
            'business_id'        => $this->businessId,
            'date_time'          => now(),
            'pump_operator_id'   => $this->pumpOperatorId,
            'amount'             => $amount,
            'deposited'          => 0,
            'balance'            => $amount,
            'shift_id'           => $shiftId,
            'collection_form_no' => 'ME-' . uniqid(),
            'p_o_payment_id'     => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        if (Schema::hasColumn('pump_operator_meter_sales', 'settlement_no')) {
            $data['settlement_no'] = $settlementNo;
        }

        if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            $data['source'] = $source;
        }

        if (Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            $data['testing_qty'] = 0;
        }

        return DB::table('pump_operator_meter_sales')->insertGetId($data);
    }
}
