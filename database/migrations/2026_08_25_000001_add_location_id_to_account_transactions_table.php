<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LA-1214: give account_transactions its own location_id.
 *
 * The table had no location of its own. Every location-filtered balance had to
 * reach one through a join - transaction_id -> transactions.location_id, or
 * journal_entry -> journals.location_id. Rows with neither link could not be
 * attributed at all.
 *
 * The column is NULLABLE on purpose. A null means "not yet attributed", and the
 * filter in Account::applyAccountTransactionLocationFilter() falls back to the
 * joins for those rows. So this migration cannot change any figure on its own -
 * it only creates somewhere better to put the answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_transactions')) {
            return;
        }

        if (! Schema::hasColumn('account_transactions', 'location_id')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->unsignedInteger('location_id')
                    ->nullable()
                    ->after('business_id')
                    ->comment('LA-1214: business location. Null = derive from transaction/journal.');
            });
        }

        // Index separately so a re-run adds it even if the column already
        // existed from a manual ALTER.
        $hasIndex = collect(DB::select(
            "SHOW INDEX FROM `account_transactions` WHERE Key_name = 'account_transactions_location_id_index'"
        ))->isNotEmpty();

        if (! $hasIndex) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->index('location_id', 'account_transactions_location_id_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('account_transactions')) {
            return;
        }

        if (Schema::hasColumn('account_transactions', 'location_id')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->dropIndex('account_transactions_location_id_index');
                $table->dropColumn('location_id');
            });
        }
    }
};
