<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')
            && Schema::hasTable('settlements')
            && Schema::hasColumn('transactions', 'petro_settlement_id')) {
            DB::statement("
                UPDATE transactions t
                JOIN settlements s
                  ON s.business_id = t.business_id
                 AND (
                      t.invoice_no = s.settlement_no
                   OR t.ref_no LIKE CONCAT('%settlement #', s.settlement_no, '%')
                   OR t.ref_no LIKE CONCAT('%Settlement No: ', s.settlement_no, '%')
                   OR t.ref_no LIKE CONCAT('%Settlement No.', s.settlement_no, '%')
                 )
                SET t.petro_settlement_id = s.id
                WHERE t.petro_settlement_id IS NULL
                  AND t.deleted_at IS NULL
            ");
        }

        if (Schema::hasTable('account_transactions')
            && Schema::hasTable('transactions')
            && Schema::hasColumn('account_transactions', 'petro_settlement_id')
            && Schema::hasColumn('transactions', 'petro_settlement_id')) {
            DB::statement("
                UPDATE account_transactions atx
                JOIN transactions t ON t.id = atx.transaction_id
                SET atx.petro_settlement_id = t.petro_settlement_id
                WHERE atx.petro_settlement_id IS NULL
                  AND t.petro_settlement_id IS NOT NULL
                  AND atx.deleted_at IS NULL
            ");
        }
    }

    public function down(): void
    {
        // Data backfill only. Intentionally not reversible.
    }
};
