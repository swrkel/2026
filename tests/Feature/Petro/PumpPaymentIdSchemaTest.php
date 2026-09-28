<?php

namespace Tests\Feature\Petro;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Schema-level sanity. Independent of behaviour — proves Step 2's migrations applied.
 * If this is red, every other test in this folder is meaningless because the column
 * the writes target does not exist.
 *
 * @group characterization
 */
class PumpPaymentIdSchemaTest extends TestCase
{
    /** @test */
    public function pump_payment_id_column_exists_on_all_four_settlement_payment_tables(): void
    {
        $this->assertTrue(Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id'),
            'Step 2 migration 2026_05_12_000001 not applied — settlement_credit_sale_payments lacks pump_payment_id.');
        $this->assertTrue(Schema::hasColumn('settlement_card_payments', 'pump_payment_id'),
            'settlement_card_payments lacks pump_payment_id.');
        $this->assertTrue(Schema::hasColumn('settlement_cash_payments', 'pump_payment_id'),
            'settlement_cash_payments lacks pump_payment_id.');
        $this->assertTrue(Schema::hasColumn('settlement_cheque_payments', 'pump_payment_id'),
            'settlement_cheque_payments lacks pump_payment_id.');
    }

    /** @test */
    public function pump_operator_payments_does_not_have_pump_payment_id_column(): void
    {
        // pump_operator_payments IS the source; pump_payment_id points TO it, not FROM it.
        $this->assertFalse(Schema::hasColumn('pump_operator_payments', 'pump_payment_id'),
            'pump_operator_payments must not have pump_payment_id — it is the referenced table, not the referring one.');
    }

    /** @test */
    public function pump_payment_id_column_is_indexed_on_settlement_credit_sale_payments(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM settlement_credit_sale_payments"))
            ->pluck('Column_name')
            ->toArray();
        $this->assertContains('pump_payment_id', $indexes,
            'settlement_credit_sale_payments.pump_payment_id must be indexed (added in Step 2 migration as idx_scsp_pump_payment_id).');
    }
}
