<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * PD Operators / Payment Summary view: one row per (settlement_no, pump_payment_id)
 * with the source pump_operator_payments.id surfaced for editing.
 *
 * @group characterization
 */
class PdOperatorsPaymentSummaryTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function summary_query_returns_one_row_per_settlement_credit_sale_with_pump_payment_id(): void
    {
        $shiftSettlementNo = 'TST-SUM-' . uniqid();
        $reconciler        = app(SettlementPaymentReconciler::class);

        $pop1 = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit', 'payment_amount' => '1000', 'settlement_no' => $shiftSettlementNo,
        ]);
        $pop2 = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit', 'payment_amount' => '2000', 'settlement_no' => $shiftSettlementNo,
        ]);

        $reconciler->upsertOne($this->businessId, $shiftSettlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $pop1, 'amount' => 1000, 'sub_total' => 1000,
            ]));
        $reconciler->upsertOne($this->businessId, $shiftSettlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData([
                'pump_payment_id' => $pop2, 'amount' => 2000, 'sub_total' => 2000,
            ]));

        $rows = DB::table('settlement_credit_sale_payments as scsp')
            ->where('scsp.business_id', $this->businessId)
            ->where('scsp.settlement_no', $shiftSettlementNo)
            ->select('scsp.id as scsp_id', 'scsp.pump_payment_id', 'scsp.amount')
            ->orderBy('scsp.id')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertEquals($pop1, (int) $rows[0]->pump_payment_id);
        $this->assertEquals($pop2, (int) $rows[1]->pump_payment_id);
        $this->assertEquals(1000, (int) $rows[0]->amount);
        $this->assertEquals(2000, (int) $rows[1]->amount);
    }

    /** @test */
    public function each_summary_row_is_uniquely_addressable_for_edit(): void
    {
        $shiftSettlementNo = 'TST-UNIQ-' . uniqid();
        $reconciler        = app(SettlementPaymentReconciler::class);

        $pop  = $this->seedPumpOperatorPayment(['payment_type' => 'credit']);
        $scsp = $reconciler->upsertOne($this->businessId, $shiftSettlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData(['pump_payment_id' => $pop]));

        $row = DB::table('settlement_credit_sale_payments')->where('id', $scsp->id)->first();
        $this->assertNotNull($row->id);
        $this->assertEquals($pop, (int) $row->pump_payment_id);
    }
}
