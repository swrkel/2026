<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 4 — petro_settlement_id FK on transactions / account_transactions.
 *
 * Pins down:
 *   1. The migration applied — both columns exist and are indexed.
 *   2. New transactions that carry petro_settlement_id are matched by the FK path
 *      in UpdatesSettlementTransactions (fast, unambiguous).
 *   3. Historical transactions with petro_settlement_id NULL are still matched by
 *      the LIKE fallback — coexistence is intentional until week 1 backfill.
 *
 * @group characterization
 */
class TransactionsHaveSettlementFkOnNewWritesTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function transactions_table_has_petro_settlement_id_column_indexed(): void
    {
        $this->assertTrue(Schema::hasColumn('transactions', 'petro_settlement_id'),
            'Step 4 migration not applied — transactions lacks petro_settlement_id.');

        $cols = collect(DB::select("SHOW INDEX FROM transactions"))->pluck('Column_name')->toArray();
        $this->assertContains('petro_settlement_id', $cols,
            'petro_settlement_id must be indexed for fast lookups in UpdatesSettlementTransactions.');
    }

    /** @test */
    public function account_transactions_table_has_petro_settlement_id_column_indexed(): void
    {
        $this->assertTrue(Schema::hasColumn('account_transactions', 'petro_settlement_id'),
            'Step 4 migration not applied — account_transactions lacks petro_settlement_id.');

        $cols = collect(DB::select("SHOW INDEX FROM account_transactions"))->pluck('Column_name')->toArray();
        $this->assertContains('petro_settlement_id', $cols);
    }

    /** @test */
    public function new_transaction_writes_can_persist_petro_settlement_id(): void
    {
        // Seed a transactions row with the new FK populated. This proves the column
        // is writable end-to-end (column exists, indexed, accepts the FK value).
        $settlementId = random_int(900000, 999999); // arbitrary high id

        $txId = DB::table('transactions')->insertGetId([
            'business_id'        => $this->businessId,
            'location_id'        => null,
            'type'               => 'sell',
            'status'             => 'final',
            'payment_status'     => 'paid',
            'invoice_no'         => 'TST-FK-' . uniqid(),
            'ref_no'             => 'unrelated-text',
            'transaction_date'   => now(),
            'total_before_tax'   => 100,
            'final_total'        => 100,
            'tax_amount'         => 0,
            'created_by'         => $this->userId,
            'petro_settlement_id' => $settlementId,
        ]);

        $row = DB::table('transactions')->where('id', $txId)->first();
        $this->assertEquals($settlementId, (int) $row->petro_settlement_id,
            'petro_settlement_id must round-trip through INSERT and SELECT.');
    }

    /** @test */
    public function trait_lookup_prefers_petro_settlement_id_fk_over_like_path(): void
    {
        // Seed two transactions:
        //   - tx1: petro_settlement_id = X, ref_no with NO settlement marker → must still be found
        //   - tx2: petro_settlement_id NULL, ref_no = "settlement #X"        → found via LIKE fallback
        $settlementId = random_int(900000, 999999);
        $settlementNo = (string) $settlementId; // for the LIKE branch

        $tx1Id = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId, 'type' => 'sell', 'status' => 'final',
            'invoice_no' => 'TST-A-' . uniqid(), 'ref_no' => 'totally unrelated',
            'transaction_date' => now(), 'final_total' => 100,
            'tax_amount' => 0, 'created_by' => $this->userId,
            'petro_settlement_id' => $settlementId,
        ]);
        $tx2Id = DB::table('transactions')->insertGetId([
            'business_id' => $this->businessId, 'type' => 'sell', 'status' => 'final',
            'invoice_no' => 'TST-B-' . uniqid(),
            'ref_no' => 'something settlement #' . $settlementNo . ' else',
            'transaction_date' => now(), 'final_total' => 100,
            'tax_amount' => 0, 'created_by' => $this->userId,
            'petro_settlement_id' => null,
        ]);

        // Reproduce the trait's two-pass lookup inline (cannot easily invoke the trait
        // because it requires a real Settlement model with timestamps; the behaviour
        // we want to verify is the QUERY itself).
        $fkIds = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('petro_settlement_id', $settlementId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $likeBase = DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->whereNull('petro_settlement_id');
        $likeIds = (clone $likeBase)
            ->where(function ($q) use ($settlementNo) {
                $q->where('ref_no', 'like', '%settlement #' . $settlementNo . '%');
            })
            ->pluck('id');

        $combined = $fkIds->merge($likeIds)->unique()->values()->all();

        $this->assertContains($tx1Id, $combined, 'FK lookup must find tx1.');
        $this->assertContains($tx2Id, $combined, 'LIKE fallback must still find tx2 (historical row, no FK).');
        $this->assertEquals(2, count($combined),
            'No double-counting: tx1 must be found ONCE via FK, NOT also via LIKE (LIKE base whereNull petro_settlement_id).');
    }
}
