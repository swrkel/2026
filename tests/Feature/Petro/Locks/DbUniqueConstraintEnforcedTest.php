<?php

namespace Tests\Feature\Petro\Locks;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Petro\PetroTestCase;

/**
 * Lock 1 — DB UNIQUE constraint on (business_id, settlement_no, pump_payment_id).
 *
 * @group characterization
 */
class DbUniqueConstraintEnforcedTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function duplicate_raw_insert_on_settlement_card_payments_throws_query_exception(): void
    {
        $popId = $this->seedPumpOperatorPayment(['payment_type' => 'card']);
        $settlementNo = 'TST-LOCK1-' . uniqid();

        $row = [
            'business_id'         => $this->businessId,
            'settlement_no'       => $settlementNo,
            'customer_id'         => $this->contactId,
            'amount'              => 500.0,
            'card_type'           => 1,
            'customer_payment_id' => null,
            'pump_payment_id'     => $popId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];

        DB::table('settlement_card_payments')->insert($row);

        try {
            DB::table('settlement_card_payments')->insert($row);
            $this->fail('Expected QueryException due to UNIQUE constraint violation.');
        } catch (QueryException $e) {
            $this->assertEquals('23000', $e->getCode(),
                'Lock 1: DB UNIQUE constraint must reject duplicate (business_id, settlement_no, pump_payment_id).');
        }
    }

    /** @test */
    public function null_pump_payment_id_rows_do_not_conflict_each_other(): void
    {
        $settlementNo = 'TST-LOCK1N-' . uniqid();

        $row = [
            'business_id'         => $this->businessId,
            'settlement_no'       => $settlementNo,
            'customer_id'         => $this->contactId,
            'amount'              => 100.0,
            'customer_payment_id' => null,
            'pump_payment_id'     => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];

        DB::table('settlement_cash_payments')->insert($row);
        DB::table('settlement_cash_payments')->insert($row);

        $this->assertEquals(2,
            DB::table('settlement_cash_payments')->where('settlement_no', $settlementNo)->count(),
            'MySQL UNIQUE treats NULL as distinct — orphan rows must not conflict.');
    }
}
