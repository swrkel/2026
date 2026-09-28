<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Http\Controllers\AddPaymentController;

class PdAddPaymentCurrentShiftIsolationTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function add_daily_cards_keeps_previous_shift_credit_sales_out_of_current_pd_settlement(): void
    {
        $previousShiftId = $this->seedShift();
        $currentShiftId = $this->seedShift();
        $collectionFormNo = 'CF-REUSED-' . uniqid();
        $previousSettlementNo = 'PDST3-' . uniqid();

        $this->seedSettlement($previousSettlementNo, $previousShiftId, 0);
        $currentSettlementId = $this->seedSettlement('PDST4-' . uniqid(), $currentShiftId, 1);
        $currentSettlementNo = (string) DB::table('settlements')
            ->where('id', $currentSettlementId)
            ->value('settlement_no');

        $previousPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3687.50,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $previousShiftId,
            'settlement_no' => $previousSettlementNo,
            'is_used' => 1,
        ]);
        $currentPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3000,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $currentShiftId,
            'settlement_no' => null,
            'is_used' => 0,
        ]);

        $previousCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => $previousPaymentId,
                'settlement_no' => $previousSettlementNo,
                'order_number' => 'OLD-' . uniqid(),
                'amount' => 3687.50,
                'sub_total' => 3687.50,
                'price' => 368.75,
                'qty' => 10,
            ])
        );
        $currentCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => $currentPaymentId,
                'settlement_no' => null,
                'order_number' => 'NEW-' . uniqid(),
                'amount' => 3000,
                'sub_total' => 3000,
                'price' => 300,
                'qty' => 10,
            ])
        );

        app(AddPaymentController::class)->addDailyCards(
            $currentSettlementId,
            $this->pumpOperatorId,
            $this->businessId,
            $currentShiftId
        );

        $this->assertSame(
            $previousSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $previousCreditSaleId)->value('settlement_no')
        );
        $this->assertSame(
            $currentSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $currentCreditSaleId)->value('settlement_no')
        );
    }

    /** @test */
    public function add_daily_cards_links_current_shift_legacy_credit_sales_without_pump_payment_id(): void
    {
        $previousShiftId = $this->seedShift();
        $currentShiftId = $this->seedShift();
        $collectionFormNo = 'CF-LEGACY-' . uniqid();
        $previousSettlementNo = 'PDST3-' . uniqid();

        $this->seedSettlement($previousSettlementNo, $previousShiftId, 0);
        $currentSettlementId = $this->seedSettlement('PDST4-' . uniqid(), $currentShiftId, 1);
        $currentSettlementNo = (string) DB::table('settlements')
            ->where('id', $currentSettlementId)
            ->value('settlement_no');

        $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3687.50,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $previousShiftId,
            'settlement_no' => $previousSettlementNo,
            'is_used' => 1,
        ]);
        $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3000,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $currentShiftId,
            'settlement_no' => null,
            'is_used' => 0,
        ]);

        $previousCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => null,
                'settlement_no' => $previousSettlementNo,
                'order_number' => 'OLD-' . uniqid(),
                'amount' => 3687.50,
                'sub_total' => 3687.50,
                'price' => 368.75,
                'qty' => 10,
            ])
        );
        $currentCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => null,
                'settlement_no' => null,
                'order_number' => 'NEW-' . uniqid(),
                'amount' => 3000,
                'sub_total' => 3000,
                'price' => 300,
                'qty' => 10,
            ])
        );

        app(AddPaymentController::class)->addDailyCards(
            $currentSettlementId,
            $this->pumpOperatorId,
            $this->businessId,
            $currentShiftId
        );

        $this->assertSame(
            $previousSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $previousCreditSaleId)->value('settlement_no')
        );
        $this->assertSame(
            $currentSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $currentCreditSaleId)->value('settlement_no')
        );
    }

    /** @test */
    public function add_daily_cards_relinks_current_shift_pumper_credit_sales_from_direct_settlement_number(): void
    {
        $currentShiftId = $this->seedShift();
        $collectionFormNo = 'CF-PUMPER-' . uniqid();
        $currentSettlementId = $this->seedSettlement('PDST4-' . uniqid(), $currentShiftId, 1);
        $currentSettlementNo = (string) DB::table('settlements')
            ->where('id', $currentSettlementId)
            ->value('settlement_no');

        $currentPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3000,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $currentShiftId,
            'settlement_no' => 'ST341',
            'is_used' => 0,
        ]);

        $currentCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => $currentPaymentId,
                'settlement_no' => 'ST341',
                'order_number' => 'PUMPER-' . uniqid(),
                'amount' => 3000,
                'sub_total' => 3000,
                'price' => 300,
                'qty' => 10,
            ])
        );

        app(AddPaymentController::class)->addDailyCards(
            $currentSettlementId,
            $this->pumpOperatorId,
            $this->businessId,
            $currentShiftId
        );

        $this->assertSame(
            $currentSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $currentCreditSaleId)->value('settlement_no')
        );
    }

    /** @test */
    public function add_daily_cards_does_not_relink_previous_closed_unsettled_shift_credit_sales(): void
    {
        $previousShiftId = $this->seedShift();
        $currentShiftId = $this->seedShift();
        $collectionFormNo = 'CF-CLOSED-' . uniqid();
        $currentSettlementId = $this->seedSettlement('PDST4-' . uniqid(), $currentShiftId, 1);
        $currentSettlementNo = (string) DB::table('settlements')
            ->where('id', $currentSettlementId)
            ->value('settlement_no');

        $previousPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3687.50,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $previousShiftId,
            'settlement_no' => 'ST340',
            'is_used' => 0,
        ]);
        $currentPaymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 3000,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionFormNo,
            'shift_id' => $currentShiftId,
            'settlement_no' => 'ST341',
            'is_used' => 0,
        ]);

        $previousCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => $previousPaymentId,
                'settlement_no' => 'ST340',
                'order_number' => 'OLD-CLOSED-' . uniqid(),
                'amount' => 3687.50,
                'sub_total' => 3687.50,
                'price' => 368.75,
                'qty' => 10,
            ])
        );
        $currentCreditSaleId = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo,
                'pump_payment_id' => $currentPaymentId,
                'settlement_no' => 'ST341',
                'order_number' => 'NEW-CLOSED-' . uniqid(),
                'amount' => 3000,
                'sub_total' => 3000,
                'price' => 300,
                'qty' => 10,
            ])
        );

        app(AddPaymentController::class)->addDailyCards(
            $currentSettlementId,
            $this->pumpOperatorId,
            $this->businessId,
            $currentShiftId
        );

        $this->assertSame(
            'ST340',
            DB::table('settlement_credit_sale_payments')->where('id', $previousCreditSaleId)->value('settlement_no')
        );
        $this->assertSame(
            $currentSettlementNo,
            DB::table('settlement_credit_sale_payments')->where('id', $currentCreditSaleId)->value('settlement_no')
        );
    }

    private function seedShift(): int
    {
        return DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now()->toDateString(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSettlement(string $settlementNo, int $shiftId, int $status): int
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        return DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([$shiftId]),
            'status' => $status,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
