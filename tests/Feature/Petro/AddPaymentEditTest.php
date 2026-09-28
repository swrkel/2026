<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Http\Controllers\AddPaymentController;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * Step 3: edits to a credit-sale settlement row keyed by primary id keep
 * pump_payment_id stable across the edit. Tests pass writes through
 * SettlementPaymentReconciler::upsertOne so Lock 2 lets them through.
 *
 * @group characterization
 */
class AddPaymentEditTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function editing_a_credit_sale_keeps_pump_payment_id_stable(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'credit']);
        $reconciler    = app(SettlementPaymentReconciler::class);
        $settlementNo  = 'TST-' . uniqid();

        $scsp = $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $pumpPaymentId,
                'amount'          => 1000,
                'sub_total'       => 1000,
                'note'            => 'original',
            ]));

        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $pumpPaymentId,
                'amount'          => 1500,
                'sub_total'       => 1500,
                'note'            => 'edited',
            ]));

        $row = DB::table('settlement_credit_sale_payments')->where('id', $scsp->id)->first();
        $this->assertEquals($pumpPaymentId, (int) $row->pump_payment_id,
            'pump_payment_id must survive an edit unchanged.');
        $this->assertEquals('edited', $row->note);
        $this->assertEquals(1500, (int) $row->amount);
    }

    /** @test */
    public function add_payment_can_match_an_existing_row_by_pump_payment_id(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'credit']);
        $reconciler    = app(SettlementPaymentReconciler::class);
        $settlementNo  = 'TST-' . uniqid();

        $scsp = $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData(['pump_payment_id' => $pumpPaymentId]));

        $found = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $this->businessId)
            ->where('pump_payment_id', $pumpPaymentId)
            ->get();

        $this->assertCount(1, $found, 'Lookup by pump_payment_id must find exactly the source row.');
        $this->assertEquals($scsp->id, (int) $found[0]->id);
    }

    /** @test */
    public function payment_to_finalize_credit_hydration_uses_reconciler_for_existing_credit_rows(): void
    {
        $shiftId = 987654;
        $collectionNo = 'CF-CREDIT-LOCK-' . uniqid();
        $settlementNo = 'PDST-CREDIT-LOCK-' . uniqid();
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([$shiftId]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 100,
            'collection_form_no' => $collectionNo,
            'shift_id' => $shiftId,
        ]);

        $scsp = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            null,
            'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionNo,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_payment_id' => $pumpPaymentId,
                'amount' => 100,
                'sub_total' => 100,
            ])
        );

        app(AddPaymentController::class)->addDailyCards(
            $settlementId,
            $this->pumpOperatorId,
            $this->businessId,
            $shiftId
        );

        $this->assertSame(
            $settlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $scsp->id)->value('settlement_no')
        );
    }

    /** @test */
    public function direct_settlement_payment_to_finalize_does_not_duplicate_realtime_credit_sales_with_daily_vouchers(): void
    {
        $shiftId = 987655;
        $settlementNo = 'PDST-CREDIT-DUP-' . uniqid();
        $collectionNo = 'CF-CREDIT-DUP-' . uniqid();
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([$shiftId]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);

        $pumpPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 796,
            'collection_form_no' => $collectionNo,
            'shift_id' => $shiftId,
            'settlement_no' => null,
        ]);

        $dailyVoucherId = DB::table('daily_vouchers')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'transaction_date' => '2026-05-20',
            'daily_vouchers_no' => $collectionNo,
            'location_id' => $locationId ?: null,
            'operator_id' => $this->pumpOperatorId,
            'customer_id' => $this->contactId,
            'voucher_order_number' => '0',
            'voucher_order_date' => '2026-05-20',
            'status' => 1,
            'created_by' => $this->userId,
            'total_amount' => 796,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            null,
            'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionNo,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_payment_id' => $pumpPaymentId,
                'daily_voucher_id' => $dailyVoucherId,
                'amount' => 796,
                'sub_total' => 796,
                'qty' => 2,
                'price' => 398,
                'order_number' => '0',
                'order_date' => '2026-05-20',
            ])
        );

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        session()->put('business.id', $this->businessId);
        session()->put('user.business_id', $this->businessId);
        session()->put('user.id', $this->userId);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no' => $settlementNo,
            'active_settlement_id' => $settlementId,
            'pump_operator_id' => $this->pumpOperatorId,
            'shift_ids' => (string) $shiftId,
            'type' => 'settlement',
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view = app(AddPaymentController::class)->create($request);
        $creditSales = $view->getData()['settlement_credit_sale_payments'];

        $this->assertCount(1, $creditSales);
        $this->assertEqualsWithDelta(796, (float) $creditSales->first()->amount, 0.001);
    }

    /** @test */
    public function pd_payment_to_finalize_hides_linked_credit_sales_from_other_shifts(): void
    {
        $previousShiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now()->toDateString(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $currentShiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now()->toDateString(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $settlementNo = 'PDST-CREDIT-SHIFT-' . uniqid();
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([$currentShiftId]),
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $previousPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 100,
            'collection_form_no' => 'PD-PREV-' . uniqid(),
            'shift_id' => $previousShiftId,
            'settlement_no' => $settlementNo,
        ]);
        $currentPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 200,
            'collection_form_no' => 'PD-CUR-' . uniqid(),
            'shift_id' => $currentShiftId,
            'settlement_no' => $settlementNo,
        ]);

        DB::table('settlement_credit_sale_payments')->insert($this->buildCreditSalePaymentData([
            'pump_payment_id' => $previousPaymentId,
            'settlement_no' => $settlementNo,
            'collection_form_no' => 'PD-PREV-' . uniqid(),
            'amount' => 100,
            'sub_total' => 100,
        ]));
        DB::table('settlement_credit_sale_payments')->insert($this->buildCreditSalePaymentData([
            'pump_payment_id' => $currentPaymentId,
            'settlement_no' => $settlementNo,
            'collection_form_no' => 'PD-CUR-' . uniqid(),
            'amount' => 200,
            'sub_total' => 200,
        ]));

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        session()->put('business.id', $this->businessId);
        session()->put('user.business_id', $this->businessId);
        session()->put('user.id', $this->userId);

        $request = Request::create('/petro/settlement/payment', 'GET', [
            'settlement_no' => $settlementNo,
            'active_settlement_id' => $settlementId,
            'pump_operator_id' => $this->pumpOperatorId,
            'shift_ids' => (string) $currentShiftId,
            'type' => 'settlement_pd',
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $view = app(AddPaymentController::class)->create($request);
        $creditSales = $view->getData()['settlement_credit_sale_payments'];

        $this->assertEqualsWithDelta(200, (float) $creditSales->sum('amount'), 0.001);
    }
}
