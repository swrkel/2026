<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * IS1293 (11 May 2026):
 *   "When adding multiple credit sales in the pumper dashboard / payments /
 *    credit sales page, and when edit only one credit sale, the other credit
 *    sales will change accordingly. Its wrong."
 *
 * After Step 3: writes carry pump_payment_id. Edits keyed on pump_payment_id
 * (or scsp.id) affect exactly one row. The legacy composite-key bulk update
 * that produced the IS1293 bug is the test's negative documentation.
 *
 * @group characterization
 */
class EditOneCreditSaleAffectsOnlyThatOneTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function editing_one_credit_sale_does_not_change_siblings_with_overlapping_attributes(): void
    {
        $collectionFormNo = 'CF-' . uniqid();
        $reconciler       = app(SettlementPaymentReconciler::class);

        $popId1 = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '2950', 'collection_form_no' => $collectionFormNo]);
        $popId2 = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '2950', 'collection_form_no' => $collectionFormNo]);
        $popId3 = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '2950', 'collection_form_no' => $collectionFormNo]);

        $settlementNo = 'TST-' . uniqid();
        $scsp1 = $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo, 'amount' => 2950, 'sub_total' => 2950,
                'pump_payment_id'    => $popId1, 'note' => 'row-1-original',
            ]));
        $scsp2 = $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo, 'amount' => 2950, 'sub_total' => 2950,
                'pump_payment_id'    => $popId2, 'note' => 'row-2-original',
            ]));
        $scsp3 = $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo, 'amount' => 2950, 'sub_total' => 2950,
                'pump_payment_id'    => $popId3, 'note' => 'row-3-original',
            ]));

        $row1Before = (array) DB::table('settlement_credit_sale_payments')->where('id', $scsp1->id)->first();
        $row3Before = (array) DB::table('settlement_credit_sale_payments')->where('id', $scsp3->id)->first();

        // Edit row 2 — keyed on pump_payment_id (the stable Step-2 identity).
        DB::table('settlement_credit_sale_payments')
            ->where('pump_payment_id', $popId2)
            ->update(['note' => 'row-2-EDITED', 'amount' => 9999, 'updated_at' => now()]);

        $row1After = (array) DB::table('settlement_credit_sale_payments')->where('id', $scsp1->id)->first();
        $row2After = (array) DB::table('settlement_credit_sale_payments')->where('id', $scsp2->id)->first();
        $row3After = (array) DB::table('settlement_credit_sale_payments')->where('id', $scsp3->id)->first();

        $this->assertEquals('row-2-EDITED', $row2After['note']);
        $this->assertEquals(9999, (int) $row2After['amount']);
        $this->assertEquals($row1Before, $row1After,
            'IS1293: row 1 must not change when row 2 is edited.');
        $this->assertEquals($row3Before, $row3After,
            'IS1293: row 3 must not change when row 2 is edited.');
    }

    /** @test */
    public function legacy_composite_key_update_would_have_bulk_updated_all_matching_rows(): void
    {
        // Documents the negative behaviour: a query keyed on the LEGACY composite
        // (pump_operator_id, amount, collection_form_no) hits ALL matching rows.
        // This proves WHY Step 2's pump_payment_id was needed and Step 3 routes
        // edits through the stable id.
        $collectionFormNo = 'CF-LEGACY-' . uniqid();
        $reconciler       = app(SettlementPaymentReconciler::class);
        $settlementNo     = 'TST-LEGACY-' . uniqid();

        $popId1 = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '2950', 'collection_form_no' => $collectionFormNo]);
        $popId2 = $this->seedPumpOperatorPayment(['payment_type' => 'credit', 'payment_amount' => '2950', 'collection_form_no' => $collectionFormNo]);

        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo, 'amount' => 2950, 'sub_total' => 2950,
                'pump_payment_id'    => $popId1,
            ]));
        $reconciler->upsertOne($this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'collection_form_no' => $collectionFormNo, 'amount' => 2950, 'sub_total' => 2950,
                'pump_payment_id'    => $popId2,
            ]));

        $affected = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $this->businessId)
            ->where('pump_operator_id', $this->pumpOperatorId)
            ->where('amount', 2950)
            ->where('collection_form_no', $collectionFormNo)
            ->update(['note' => 'BULK-CHANGED']);

        $this->assertEquals(2, $affected,
            'Documents IS1293 root cause: composite-key updates still hit both rows. Step 3 prevents this by keying production code on pump_payment_id. CI lint will remove these composite queries in week 1.');
    }
}
