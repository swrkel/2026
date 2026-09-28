<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * S 235 / S 237 / S 238 / IS1216:
 *   "Total Amount is showing as zero."
 *   "Total Sale, Total Paid & Balance are not showing correctly."
 *
 * Root cause: aggregations summed duplicate rows produced by unguarded ::create().
 * Step 3 fix: Reconciler upsertOne idempotency means SUM(amount) is now stable
 * across repeated edits of the same source payment.
 *
 * @group characterization
 */
class PaymentToFinalizeTotalsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function correctly_sums_three_distinct_card_payments_for_one_settlement(): void
    {
        $settlementNo = 'TST-TOT-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);

        foreach ([1100.0, 980.0, 2200.0] as $amount) {
            $popId = $this->seedPumpOperatorPayment([
                'payment_type'   => 'card',
                'payment_amount' => (string) $amount,
            ]);
            $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
                'customer_id'         => $this->contactId,
                'amount'              => $amount,
                'card_type'           => 1,
                'customer_payment_id' => null,
                'pump_payment_id'     => $popId,
            ]);
        }

        $cardTotal = (float) DB::table('settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->sum('amount');

        $this->assertEqualsWithDelta(4280.0, $cardTotal, 0.001,
            'Sum over three distinct card payments must equal their individual amounts.');
    }

    /** @test */
    public function repeated_upserts_no_longer_inflate_the_total_fixing_the_S237_bug(): void
    {
        $settlementNo  = 'TST-DUP-TOT-' . uniqid();
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '1100']);

        $cardData = [
            'customer_id'         => $this->contactId,
            'amount'              => 1100.0,
            'card_type'           => 1,
            'customer_payment_id' => null,
            'pump_payment_id'     => $pumpPaymentId,
        ];

        // Simulate "edited twice and saved" — the S237 scenario.
        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);

        $total = (float) DB::table('settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->sum('amount');

        // Step 3 flipped: was 2200 under the bug.
        $this->assertEqualsWithDelta(1100.0, $total, 0.001,
            'S 237 fixed: repeated upserts no longer double the Payment-to-Finalize total.');
    }

    /** @test */
    public function payment_to_finalize_total_combines_cash_card_and_credit_sale(): void
    {
        $settlementNo = 'TST-COMBO-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);

        $cash = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'payment_amount' => '500']);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id'         => $this->contactId,
            'amount'              => 500.0,
            'customer_payment_id' => null,
            'pump_payment_id'     => $cash,
        ]);

        $card = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '300']);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
            'customer_id'         => $this->contactId,
            'amount'              => 300.0,
            'card_type'           => 1,
            'customer_payment_id' => null,
            'pump_payment_id'     => $card,
        ]);

        $credit = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '700']);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'amount'          => 700,
                'sub_total'       => 700,
                'pump_payment_id' => $credit,
            ]));

        $cashTotal   = (float) DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->sum('amount');
        $cardTotal   = (float) DB::table('settlement_card_payments')->where('settlement_no', $settlementNo)->sum('amount');
        $creditTotal = (float) DB::table('settlement_credit_sale_payments')->where('settlement_no', $settlementNo)->sum('amount');

        $this->assertEqualsWithDelta(500.0, $cashTotal, 0.001);
        $this->assertEqualsWithDelta(300.0, $cardTotal, 0.001);
        $this->assertEqualsWithDelta(700.0, $creditTotal, 0.001);
        $this->assertEqualsWithDelta(1500.0, $cashTotal + $cardTotal + $creditTotal, 0.001,
            'S 238: Total Amount on Payment-to-Finalize must equal cash + card + credit sums.');
    }
}
