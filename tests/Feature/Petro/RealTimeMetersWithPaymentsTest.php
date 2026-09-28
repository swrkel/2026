<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * Real-Time Entries / meters-with-payments view: pump_operator_payments.id
 * IS the pump_payment_id used by settlement_*_payments rows. The 1:1 join
 * works both directions after Step 2's column + Step 3's write routing.
 *
 * @group characterization
 */
class RealTimeMetersWithPaymentsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pump_operator_payments_can_be_joined_to_settlement_card_payments_via_pump_payment_id(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '500']);
        $settlementNo = 'TST-RT-' . uniqid();

        app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_card_payments',
            [
                'customer_id'         => $this->contactId,
                'amount'              => 500.0,
                'card_type'           => 1,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]
        );

        $rows = DB::table('pump_operator_payments as pop')
            ->join('settlement_card_payments as scp', 'scp.pump_payment_id', '=', 'pop.id')
            ->where('pop.id', $popId)
            ->select('pop.id as pop_id', 'scp.amount as scp_amount', 'scp.settlement_no')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals($popId, (int) $rows[0]->pop_id);
        $this->assertEqualsWithDelta(500.0, (float) $rows[0]->scp_amount, 0.001);
        $this->assertEquals($settlementNo, $rows[0]->settlement_no);
    }

    /** @test */
    public function meters_with_payments_dataset_carries_pump_payment_identity_for_edits(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '999']);

        $row = DB::table('pump_operator_payments')
            ->where('id', $popId)
            ->select('id', 'business_id', 'pump_operator_id', 'payment_amount', 'payment_type')
            ->first();

        $this->assertNotNull($row);
        $this->assertEquals($popId, (int) $row->id);
        $this->assertEquals($this->pumpOperatorId, (int) $row->pump_operator_id);
        $this->assertEquals('card', $row->payment_type);
    }
}
