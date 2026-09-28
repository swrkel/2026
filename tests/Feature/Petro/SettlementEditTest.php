<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\SettlementPDController;
use Modules\Petro\Services\SettlementPaymentReconciler;
use ReflectionMethod;

/**
 * S 237 / IS1293 — "Cash / Card payments are duplicate. Need to correct this issue."
 *
 * After Step 3: writes route through SettlementPaymentReconciler::upsertOne().
 * Two upserts with the same (settlement_no, pump_payment_id) produce ONE row
 * (the second call updates the first). Lock 1's UNIQUE constraint is a backstop;
 * Lock 2 (the model guard) prevents direct ::create() bypasses at runtime.
 *
 * @group characterization
 */
class SettlementEditTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function duplicate_card_payment_inserts_with_same_source_now_collapse_to_one(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '500.00']);
        $settlementNo  = 'TST-' . uniqid();

        $cardData = [
            'customer_id'         => $this->contactId,
            'amount'              => 500.00,
            'card_type'           => 1,
            'customer_payment_id' => null,
            'pump_payment_id'     => $pumpPaymentId,
        ];

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);

        $count = DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', $settlementNo)
            ->where('pump_payment_id', $pumpPaymentId)
            ->count();

        // Step 3 flipped: was assertEquals(2, $count) under the duplicate bug.
        $this->assertEquals(1, $count,
            'Step 3 fix: two upsertOne calls with same source produce exactly 1 row (idempotency).');
    }

    /** @test */
    public function duplicate_credit_sale_inserts_with_same_source_now_collapse_to_one(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'credit']);
        $settlementNo  = 'TST-' . uniqid();

        $data = $this->buildCreditSalePaymentData(['pump_payment_id' => $pumpPaymentId]);

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments', $data);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments', $data);

        $count = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', $settlementNo)
            ->where('pump_payment_id', $pumpPaymentId)
            ->count();

        // Step 3 flipped: was assertEquals(2, $count).
        $this->assertEquals(1, $count,
            'Step 3 fix: settlement_credit_sale_payments collapses duplicate (settlement_no, pump_payment_id) writes to 1 row.');
    }

    /** @test */
    public function destroy_branch_uses_reconciler_wipe_primitive_for_payment_tables(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../../Modules/Petro/Http/Controllers/SettlementPDController.php');
        $this->assertStringContainsString('if ($is_destory) {', $controller,
            'destroy wipe branch must remain (intentional behaviour).');
        $this->assertStringContainsString('wipeAllForSettlement', $controller,
            'destroy path must use the reconciler wipe primitive so orphan payment rows are deleted too.');
    }
    /** @test */
    public function finalized_pd_edit_meter_total_uses_shift_number_to_find_real_shift_id(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 0,
            'shift_date'       => now(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'      => 'PDST-' . uniqid(),
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode(['10']),
            'note'               => null,
            'total_amount'       => '345.670',
            'status'             => 0,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id'          => $this->businessId,
            'pump_id'              => $pumpId,
            'pump_operator_id'     => $this->pumpOperatorId,
            'starting_meter'       => 0,
            'closing_meter'        => 0,
            'date_and_time'        => now(),
            'close_date_and_time'  => now(),
            'status'               => 'close',
            'settlement_id'        => null,
            'assigned_by'          => $this->userId,
            'is_confirmed'         => 1,
            'confirmed_at'         => now(),
            'is_manually_closed'   => 0,
            'closed_in_settlement' => 1,
            'shift_id'             => $shiftId,
            'shift_number'         => 10,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $sale = [
            'business_id'        => $this->businessId,
            'date_time'          => now(),
            'pump_operator_id'   => $this->pumpOperatorId,
            'amount'             => 345.670,
            'deposited'          => 0,
            'balance'            => 345.670,
            'shift_id'           => $shiftId,
            'collection_form_no' => 'TST-' . uniqid(),
            'p_o_payment_id'     => null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
        if (DB::getSchemaBuilder()->hasColumn('pump_operator_meter_sales', 'settlement_no')) {
            $sale['settlement_no'] = '';
        }
        if (DB::getSchemaBuilder()->hasColumn('pump_operator_meter_sales', 'source')) {
            $sale['source'] = 'closing';
        }
        if (DB::getSchemaBuilder()->hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            $sale['testing_qty'] = 0;
        }
        DB::table('pump_operator_meter_sales')->insert($sale);

        $settlement = Settlement::findOrFail($settlementId);
        $controller = app(SettlementPDController::class);
        $method = new ReflectionMethod($controller, 'getSettlementPDMeterSaleTotal');
        $method->setAccessible(true);

        $this->assertSame(345.67, round($method->invoke($controller, $this->businessId, $settlement), 2));
    }

    /** @test */
    public function finalized_pd_edit_meter_total_falls_back_to_regular_meter_sales(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'      => 'PDST-' . uniqid(),
            'business_id'        => $this->businessId,
            'transaction_date'   => now()->toDateString(),
            'finish_date'        => now()->toDateString(),
            'location_id'        => $locationId,
            'pump_operator_id'   => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift'         => json_encode([17]),
            'note'               => null,
            'total_amount'       => '799565.64',
            'status'             => 0,
            'is_edit'            => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        DB::table('meter_sales')->insert([
            [
                'settlement_no'   => (string) $settlementId,
                'business_id'     => $this->businessId,
                'product_id'      => $this->productId,
                'pump_id'         => $pumpId,
                'starting_meter'  => 100,
                'closing_meter'   => 200,
                'price'           => 10,
                'qty'             => 100,
                'discount'        => 0,
                'discount_type'   => null,
                'discount_amount' => 1000,
                'testing_qty'     => 0,
                'sub_total'       => 1000,
                'shift_id'        => 17,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'settlement_no'   => (string) $settlementId,
                'business_id'     => $this->businessId,
                'product_id'      => $this->productId,
                'pump_id'         => $pumpId,
                'starting_meter'  => 200,
                'closing_meter'   => 250,
                'price'           => 5,
                'qty'             => 50,
                'discount'        => 0,
                'discount_type'   => null,
                'discount_amount' => 250,
                'testing_qty'     => 0,
                'sub_total'       => 250,
                'shift_id'        => 17,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        $settlement = Settlement::with('meter_sales')->findOrFail($settlementId);
        $controller = app(SettlementPDController::class);
        $method = new ReflectionMethod($controller, 'getSettlementPDMeterSaleTotal');
        $method->setAccessible(true);

        $this->assertSame(1250.0, $method->invoke($controller, $this->businessId, $settlement));
    }

}
