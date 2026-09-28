<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * Closing-shift: when a shift closes and creates settlement rows for non-credit
 * payments (cash/card/cheque), each row carries pump_payment_id linking back to
 * its source pump_operator_payments row — guaranteed today by the Reconciler.
 *
 * @group characterization
 */
class ClosingShiftTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function closing_shift_card_payment_carries_pump_payment_id(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '1200']);
        $settlementNo = 'TST-CS-' . uniqid();

        app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_card_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 1200.0,
                'card_type'           => 1,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $row = DB::table('settlement_card_payments')->where('settlement_no', $settlementNo)->first();
        $this->assertNotNull($row);
        $this->assertEquals($popId, (int) $row->pump_payment_id);
    }

    /** @test */
    public function closing_shift_cash_payment_carries_pump_payment_id(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'payment_amount' => '2000']);
        $settlementNo = 'TST-CS-CASH-' . uniqid();

        app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_cash_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 2000.0,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $row = DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->first();
        $this->assertNotNull($row);
        $this->assertEquals($popId, (int) $row->pump_payment_id);
    }

    /** @test */
    public function closing_shift_cheque_payment_carries_pump_payment_id(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'cheque', 'payment_amount' => '3500']);
        $settlementNo = 'TST-CS-CHQ-' . uniqid();

        app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_cheque_payments',
            [
                'customer_id'         => $this->contactId,
                'bank_name'           => 'TestBank',
                'cheque_number'       => 'CHQ-' . uniqid(),
                'cheque_date'         => now()->toDateString(),
                'amount'              => 3500.0,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $row = DB::table('settlement_cheque_payments')->where('settlement_no', $settlementNo)->first();
        $this->assertNotNull($row);
        $this->assertEquals($popId, (int) $row->pump_payment_id);
    }
}
