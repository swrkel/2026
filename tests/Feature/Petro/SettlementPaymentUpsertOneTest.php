<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * Step 3 load-bearing test suite: SettlementPaymentReconciler::upsertOne().
 *
 * This is the method called from every Step 3 call-site replacement of ::create().
 * Each case here is a property the upsert must hold for the duplicate-row bug
 * class to be killed forever.
 *
 * @group characterization
 */
class SettlementPaymentUpsertOneTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function no_existing_row_creates_a_new_one_with_pump_payment_id_persisted(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card']);
        $settlementNo = 'TST-' . uniqid();

        $model = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_card_payments',
            [
                'customer_id' => $this->contactId,
                'amount'      => 100.0,
                'card_type'   => 1,
                'pump_payment_id' => $popId,
            ]
        );

        $this->assertNotNull($model->id);
        $row = DB::table('settlement_card_payments')->where('id', $model->id)->first();
        $this->assertEquals($popId, (int) $row->pump_payment_id);
        $this->assertEquals(1,
            DB::table('settlement_card_payments')->where('settlement_no', $settlementNo)->count());
    }

    /** @test */
    public function existing_row_with_same_key_is_updated_not_duplicated(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);
        $settlementNo = 'TST-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);

        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 100.0,
            'pump_payment_id' => $popId,
        ]);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId,
            'amount' => 250.0,
            'pump_payment_id' => $popId,
        ]);

        $rows = DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->get();
        $this->assertCount(1, $rows, 'Existing row must be updated, not duplicated.');
        $this->assertEqualsWithDelta(250.0, (float) $rows[0]->amount, 0.001);
    }

    /** @test */
    public function sibling_rows_with_different_pump_payment_id_are_NOT_deleted(): void
    {
        // This is the regression test for the v4 sibling-delete bug.
        // upsertOne MUST NOT delete any rows, ever.
        $pop1 = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '100']);
        $pop2 = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '200']);
        $settlementNo = 'TST-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);

        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
            'customer_id' => $this->contactId, 'amount' => 100, 'card_type' => 1,
            'pump_payment_id' => $pop1,
        ]);
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
            'customer_id' => $this->contactId, 'amount' => 200, 'card_type' => 1,
            'pump_payment_id' => $pop2,
        ]);

        // Upsert pop1 again — pop2's row must remain untouched.
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', [
            'customer_id' => $this->contactId, 'amount' => 150, 'card_type' => 1,
            'pump_payment_id' => $pop1,
        ]);

        $this->assertEquals(2,
            DB::table('settlement_card_payments')->where('settlement_no', $settlementNo)->count(),
            'upsertOne must never delete sibling rows (v4 sibling-delete bug regression test).');
    }

    /** @test */
    public function null_pump_payment_id_creates_manual_orphan_payment_row(): void
    {
        $settlementNo = 'TST-' . uniqid();

        $model = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId,
            $settlementNo,
            'settlement_card_payments',
            ['customer_id' => $this->contactId, 'amount' => 50, 'pump_payment_id' => null]
        );

        $row = DB::table('settlement_card_payments')->where('id', $model->id)->first();

        $this->assertNotNull($row);
        $this->assertNull($row->pump_payment_id);
        $this->assertEquals(50, (float) $row->amount);
    }

    /** @test */
    public function manual_add_payment_tables_accept_rows_without_pump_payment_id(): void
    {
        $settlementNo = 'TST-' . uniqid();
        $reconciler = app(SettlementPaymentReconciler::class);

        $cases = [
            'settlement_cash_payments' => [
                'customer_id' => $this->contactId,
                'amount' => 50,
            ],
            'settlement_card_payments' => [
                'customer_id' => $this->contactId,
                'amount' => 60,
                'card_type' => 1,
            ],
            'settlement_cheque_payments' => [
                'customer_id' => $this->contactId,
                'amount' => 70,
                'bank_name' => 'Test Bank',
                'cheque_number' => 'CHK-' . uniqid(),
                'cheque_date' => now()->toDateString(),
            ],
            'settlement_credit_sale_payments' => $this->buildCreditSalePaymentData([
                'amount' => 80,
                'sub_total' => 80,
                'pump_payment_id' => null,
            ]),
        ];

        foreach ($cases as $table => $row) {
            $model = $reconciler->upsertOne($this->businessId, $settlementNo, $table, $row);

            $this->assertSame(1, DB::table($table)
                ->where('id', $model->id)
                ->whereNull('pump_payment_id')
                ->count(), "{$table} should allow manual rows without pump_payment_id.");
        }
    }


    /**
     * @test
     *
     * IDEMPOTENCY GATE — load-bearing. If this fails, Step 3 has not
     * actually killed the duplicate-row class.
     */
    public function calling_upsert_one_twice_yields_one_row_with_final_values(): void
    {
        $popId        = $this->seedPumpOperatorPayment(['payment_type' => 'card']);
        $settlementNo = 'TST-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);

        $data = [
            'customer_id'     => $this->contactId,
            'amount'          => 300.0,
            'card_type'       => 1,
            'pump_payment_id' => $popId,
            'note'            => 'first-call',
        ];
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $data);

        $data['amount'] = 450.0;
        $data['note']   = 'second-call';
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_card_payments', $data);

        $rows = DB::table('settlement_card_payments')->where('settlement_no', $settlementNo)->get();
        $this->assertCount(1, $rows, 'Idempotency: 2 calls = 1 row.');
        $this->assertEqualsWithDelta(450.0, (float) $rows[0]->amount, 0.001);
        $this->assertEquals('second-call', $rows[0]->note);
    }

    /** @test */
    public function null_settlement_no_uses_whereNull_not_eq_null_in_lookup(): void
    {
        // Eloquent ->where('settlement_no', null) is WRONG (SQL: x = NULL → always false).
        // Must be ->whereNull('settlement_no'). This test pins it.
        $popId      = $this->seedPumpOperatorPayment(['payment_type' => 'credit']);
        $reconciler = app(SettlementPaymentReconciler::class);

        $reconciler->upsertOne($this->businessId, null, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $popId, 'amount' => 100, 'sub_total' => 100,
            ]));
        $reconciler->upsertOne($this->businessId, null, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $popId, 'amount' => 200, 'sub_total' => 200,
            ]));

        $rows = DB::table('settlement_credit_sale_payments')
            ->where('pump_payment_id', $popId)
            ->whereNull('settlement_no')
            ->get();
        $this->assertCount(1, $rows, 'Null settlement_no lookup must use whereNull and update existing row.');
        $this->assertEqualsWithDelta(200, (float) $rows[0]->amount, 0.001);
    }

    /** @test */
    public function alternate_identity_key_customer_payment_id_works(): void
    {
        $settlementNo = 'TST-' . uniqid();
        $reconciler   = app(SettlementPaymentReconciler::class);
        $customerPaymentId = 999000 + random_int(1, 999); // arbitrary, customer_payments has no FK enforcement at this level

        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId, 'amount' => 100, 'customer_payment_id' => $customerPaymentId,
        ], 'customer_payment_id');
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_cash_payments', [
            'customer_id' => $this->contactId, 'amount' => 200, 'customer_payment_id' => $customerPaymentId,
        ], 'customer_payment_id');

        $rows = DB::table('settlement_cash_payments')
            ->where('settlement_no', $settlementNo)
            ->where('customer_payment_id', $customerPaymentId)
            ->get();
        $this->assertCount(1, $rows, 'Identity key=customer_payment_id must dedupe correctly for AddPayment flow.');
        $this->assertEqualsWithDelta(200, (float) $rows[0]->amount, 0.001);
    }
}
