<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 4 — Add petro_settlement_id FK column to transactions and account_transactions.
 *
 * Replaces the LIKE-based identity lookup in UpdatesSettlementTransactions with
 * a real FK. No backfill — historical rows keep relying on the LIKE fallback
 * (which remains as a coexistence path until week 1 backfills).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'petro_settlement_id')) {
            Schema::table('transactions', function (Blueprint $t) {
                $t->unsignedInteger('petro_settlement_id')->nullable()->after('id');
                $t->index('petro_settlement_id', 'idx_transactions_petro_settlement_id');
            });
        }

        if (Schema::hasTable('account_transactions') && ! Schema::hasColumn('account_transactions', 'petro_settlement_id')) {
            Schema::table('account_transactions', function (Blueprint $t) {
                $t->unsignedInteger('petro_settlement_id')->nullable()->after('id');
                $t->index('petro_settlement_id', 'idx_account_transactions_petro_settlement_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'petro_settlement_id')) {
            Schema::table('transactions', function (Blueprint $t) {
                $t->dropIndex('idx_transactions_petro_settlement_id');
                $t->dropColumn('petro_settlement_id');
            });
        }
        if (Schema::hasTable('account_transactions') && Schema::hasColumn('account_transactions', 'petro_settlement_id')) {
            Schema::table('account_transactions', function (Blueprint $t) {
                $t->dropIndex('idx_account_transactions_petro_settlement_id');
                $t->dropColumn('petro_settlement_id');
            });
        }
    }
};
