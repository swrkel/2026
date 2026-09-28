<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * IS1293 — "When editing card payments, shows all card payments are duplicate
 * in the Accounting module / List accounts / Card account book"
 * S 237 — "Cash / Card payments are duplicate."
 *
 * Step 3 fix: Reconciler upsertOne + Lock 1 DB UNIQUE constraint + Lock 2
 * Eloquent guard means N edits = N upserts = exactly 1 row in DB per source
 * (business_id, settlement_no, pump_payment_id) key.
 *
 * @group characterization
 */
class NoDuplicateAccountingRowsAfterMultipleEditsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function three_card_upserts_with_the_same_source_produce_one_row(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '1100']);
        $settlementNo  = 'TST-CARD-' . uniqid();

        $cardData = [
            'customer_id'         => $this->contactId,
            'amount'              => 1100.00,
            'card_type'           => 1,
            'customer_payment_id' => null,
            'pump_payment_id'     => $pumpPaymentId,
        ];

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $cardData);

        $count = DB::table('settlement_card_payments')
            ->where('settlement_no', $settlementNo)
            ->where('pump_payment_id', $pumpPaymentId)
            ->count();

        // Step 3 flipped: was 3, now 1.
        $this->assertEquals(1, $count,
            'IS1293/S237: 3 upsertOne calls with same source produce exactly 1 row.');
    }

    /** @test */
    public function three_cheque_upserts_with_the_same_source_produce_one_row(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'cheque', 'payment_amount' => '900']);
        $settlementNo  = 'TST-CHQ-' . uniqid();

        $chequeData = [
            'customer_id'         => $this->contactId,
            'bank_name'           => 'TestBank',
            'cheque_number'       => 'CHQ-' . uniqid(),
            'cheque_date'         => now()->toDateString(),
            'amount'              => 900.00,
            'customer_payment_id' => null,
            'pump_payment_id'     => $pumpPaymentId,
        ];

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cheque_payments', $chequeData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cheque_payments', $chequeData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cheque_payments', $chequeData);

        $count = DB::table('settlement_cheque_payments')
            ->where('settlement_no', $settlementNo)
            ->where('pump_payment_id', $pumpPaymentId)
            ->count();

        $this->assertEquals(1, $count,
            'S237 cheque idempotency.');
    }

    /** @test */
    public function two_cash_upserts_with_the_same_source_produce_one_row(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);
        $settlementNo  = 'TST-CASH-' . uniqid();

        $cashData = [
            'customer_id'         => $this->contactId,
            'amount'              => 1500.00,
            'customer_payment_id' => null,
            'pump_payment_id'     => $pumpPaymentId,
        ];

        $reconciler = app(SettlementPaymentReconciler::class);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', $cashData);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', $cashData);

        $count = DB::table('settlement_cash_payments')
            ->where('settlement_no', $settlementNo)
            ->where('pump_payment_id', $pumpPaymentId)
            ->count();

        // Step 3 flipped: was 2, now 1.
        $this->assertEquals(1, $count,
            'Step 3: cash upsertOne is idempotent.');
    }
}
