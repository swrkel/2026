<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('settlement_card_payments') && ! Schema::hasColumn('settlement_card_payments', 'pump_payment_id')) {
            Schema::table('settlement_card_payments', function (Blueprint $table) {
                $table->unsignedInteger('pump_payment_id')->nullable()->index('settlement_card_payments_pump_payment_id');
            });
        }

        if (Schema::hasTable('settlement_cash_payments') && ! Schema::hasColumn('settlement_cash_payments', 'pump_payment_id')) {
            Schema::table('settlement_cash_payments', function (Blueprint $table) {
                $table->unsignedInteger('pump_payment_id')->nullable()->index('settlement_cash_payments_pump_payment_id');
            });
        }

        if (Schema::hasTable('settlement_cheque_payments') && ! Schema::hasColumn('settlement_cheque_payments', 'pump_payment_id')) {
            Schema::table('settlement_cheque_payments', function (Blueprint $table) {
                $table->unsignedInteger('pump_payment_id')->nullable()->index('settlement_cheque_payments_pump_payment_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('settlement_card_payments') && Schema::hasColumn('settlement_card_payments', 'pump_payment_id')) {
            Schema::table('settlement_card_payments', function (Blueprint $table) {
                $table->dropIndex('settlement_card_payments_pump_payment_id');
                $table->dropColumn('pump_payment_id');
            });
        }

        if (Schema::hasTable('settlement_cash_payments') && Schema::hasColumn('settlement_cash_payments', 'pump_payment_id')) {
            Schema::table('settlement_cash_payments', function (Blueprint $table) {
                $table->dropIndex('settlement_cash_payments_pump_payment_id');
                $table->dropColumn('pump_payment_id');
            });
        }

        if (Schema::hasTable('settlement_cheque_payments') && Schema::hasColumn('settlement_cheque_payments', 'pump_payment_id')) {
            Schema::table('settlement_cheque_payments', function (Blueprint $table) {
                $table->dropIndex('settlement_cheque_payments_pump_payment_id');
                $table->dropColumn('pump_payment_id');
            });
        }
    }
};
