<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8030: stores the auto-incrementing reference for Finance operations.
 *
 * Cash Deposit (CAD), Cheque to Realize (CHR), Cheque Deposit (CHD),
 * Card Deposit (CDD) and Transfers (TFR) each need their own numbering series,
 * shown in the description column of every account book and ledger.
 *
 * WHY A NEW COLUMN
 * account_transactions has no free column for this:
 *
 *   slip_no  - already in use. The deposits screen offers it as a searchable
 *              filter for the real bank slip number the user types in, so
 *              writing generated references there would break that search and
 *              overwrite entered data.
 *   txn      - referenced in the Finance code but does NOT exist on the
 *              database; the reference is to a computed alias, not a column.
 *
 * SAFETY
 *  - Additive and nullable. Existing rows are untouched and simply have it empty.
 *  - Skipped entirely if the table is absent or the column already exists, so
 *    it is safe to run more than once and on tenants at different states.
 *  - Indexed, because the generator reads the highest existing number per
 *    prefix on every save.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_transactions')
            || Schema::hasColumn('account_transactions', 'finance_ref_no')) {
            return;
        }

        Schema::table('account_transactions', function (Blueprint $table): void {
            /*
             | Position is not assumed.
             |
             | after('slip_no') fails if that column is absent on a given tenant,
             | and tenants here are known to differ - customer_notes.deleted_at is
             | missing on at least one. Column ORDER has no bearing on behaviour,
             | so it is only requested when the anchor genuinely exists.
             */
            $column = $table->string('finance_ref_no', 64)->nullable();

            if (Schema::hasColumn('account_transactions', 'slip_no')) {
                $column->after('slip_no');
            }

            $table->index('finance_ref_no', 'account_transactions_finance_ref_no_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('account_transactions')
            || ! Schema::hasColumn('account_transactions', 'finance_ref_no')) {
            return;
        }

        Schema::table('account_transactions', function (Blueprint $table): void {
            $table->dropIndex('account_transactions_finance_ref_no_index');
            $table->dropColumn('finance_ref_no');
        });
    }
};
