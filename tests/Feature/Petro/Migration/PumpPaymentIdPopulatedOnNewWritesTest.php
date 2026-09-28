<?php

namespace Tests\Feature\Petro\Migration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Services\SettlementPaymentReconciler;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Step 2 + 3 acceptance: new credit-sale rows persist pump_payment_id correctly
 * via the actual Reconciler write path. Orphan path still permitted.
 *
 * @group characterization
 */
class PumpPaymentIdPopulatedOnNewWritesTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function credit_sale_upsert_persists_pump_payment_id_from_source(): void
    {
        $pumpPaymentId = $this->seedPumpOperatorPayment();
        $settlementNo  = 'TST-' . uniqid();

        $scsp = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, $settlementNo, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData(['pump_payment_id' => $pumpPaymentId])
        );

        $row = DB::table('settlement_credit_sale_payments')->where('id', $scsp->id)->first();

        $this->assertNotNull($row);
        $this->assertNotNull($row->pump_payment_id);
        $this->assertEquals($pumpPaymentId, (int) $row->pump_payment_id);
    }

    /** @test */
    public function credit_sale_orphan_upsert_keeps_pump_payment_id_null(): void
    {
        $scsp = app(SettlementPaymentReconciler::class)->upsertOne(
            $this->businessId, null, 'settlement_credit_sale_payments',
            $this->buildCreditSalePaymentData(['is_from_pumper' => 0])
        );

        $row = DB::table('settlement_credit_sale_payments')->where('id', $scsp->id)->first();
        $this->assertNotNull($row);
        $this->assertNull($row->pump_payment_id,
            'Orphan write path must leave pump_payment_id NULL (see docs/refactor/day1-orphan-writes.md).');
    }
}
