<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IS1313 fix prerequisite — add transaction_id column to the three settlement
 * payment tables that lack it. settlement_credit_sale_payments already has the
 * column; card/cash/cheque do not.
 *
 * Without this column, SettlementPaymentEditService::editCardPayment /
 * editCashPayment / editChequePayment cannot cascade an amount change to the
 * linked Transaction / AccountTransaction / ContactLedger rows, which is what
 * IS1313 reported as visible duplicates in the card/cash account book and
 * customer ledger.
 */
return new class extends Migration
{
    private const TABLES = [
        'settlement_card_payments',
        'settlement_cash_payments',
        'settlement_cheque_payments',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'transaction_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unsignedInteger('transaction_id')->nullable()->after('pump_payment_id');
                $t->index('transaction_id', "idx_{$table}_transaction_id");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'transaction_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropIndex("idx_{$table}_transaction_id");
                $t->dropColumn('transaction_id');
            });
        }
    }
};
