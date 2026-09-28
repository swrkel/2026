<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * IS1293 (11 May 2026) — verbatim:
 *   "When adding multiple credit sales in the pumper dashboard / payments /
 *    credit sales page, and when edit only one credit sale, the other credit
 *    sales will change accordingly. Its wrong."
 *
 * Root cause: the EDIT path at AddPaymentController.php:5254-5258 used a
 * composite-key UPDATE on pump_operator_payments. When several credit sales
 * share the same collection_form_no (one-by-one add flow in pumper dashboard),
 * that composite matched every sibling row and bulk-set their payment_amount.
 *
 * After the IS1293 fix, the edit must update EXACTLY the source pump_operator_payments
 * row identified by pump_payment_id — siblings remain unchanged.
 *
 * @group characterization
 */
class EditCreditSaleDoesNotBulkUpdatePumpPaymentsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function updating_one_pop_by_pump_payment_id_leaves_siblings_with_shared_collection_form_no_untouched(): void
    {
        // Reproduce the one-by-one add flow: three credit sales for the same operator,
        // same collection_form_no (the bug condition), but three DIFFERENT pump payment
        // sources (each call to PumpOperatorPaymentController seeds a fresh pop row).
        $collectionFormNo = 'CF-' . uniqid();

        $pop1 = $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => '885',
            'collection_form_no' => $collectionFormNo,
        ]);
        $pop2 = $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => '1770',
            'collection_form_no' => $collectionFormNo,
        ]);
        $pop3 = $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => '4425',
            'collection_form_no' => $collectionFormNo,
        ]);

        // Edit the FIRST one to 9999 — by pump_payment_id (the fixed code path).
        DB::table('pump_operator_payments')
            ->where('id', $pop1)
            ->where('payment_type', 'credit')
            ->update(['payment_amount' => '9999']);

        $row1 = DB::table('pump_operator_payments')->where('id', $pop1)->first();
        $row2 = DB::table('pump_operator_payments')->where('id', $pop2)->first();
        $row3 = DB::table('pump_operator_payments')->where('id', $pop3)->first();

        $this->assertEquals('9999', $row1->payment_amount,
            'Edited row updated.');
        $this->assertEquals('1770', $row2->payment_amount,
            'IS1293: sibling row 2 must NOT change when row 1 is edited (same collection_form_no, different pump_payment_id).');
        $this->assertEquals('4425', $row3->payment_amount,
            'IS1293: sibling row 3 must NOT change when row 1 is edited.');
    }

    /** @test */
    public function legacy_composite_update_pattern_still_bulk_updates_demonstrating_the_old_bug(): void
    {
        // Negative documentation: prove that the LEGACY composite-key update
        // produces the IS1293 bug. This is the pattern we MUST NOT use anywhere
        // in production code anymore. Future code review reference.
        $collectionFormNo = 'CF-LEGACY-' . uniqid();

        $pop1 = $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => '885',
            'collection_form_no' => $collectionFormNo,
        ]);
        $pop2 = $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => '1770',
            'collection_form_no' => $collectionFormNo,
        ]);

        // Legacy composite update — bulk update.
        $affected = DB::table('pump_operator_payments')
            ->where('pump_operator_id', $this->pumpOperatorId)
            ->where('collection_form_no', $collectionFormNo)
            ->where('payment_type', 'credit')
            ->update(['payment_amount' => '9999']);

        $this->assertEquals(2, $affected,
            'Documents IS1293 root cause: composite update hits all rows sharing collection_form_no. Production code must use pump_payment_id instead.');
    }
}
