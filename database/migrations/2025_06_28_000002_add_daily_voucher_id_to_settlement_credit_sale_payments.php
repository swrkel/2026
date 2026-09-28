<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDailyVoucherIdToSettlementCreditSalePayments extends Migration
{
    public function up()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->unsignedInteger('daily_voucher_id')->nullable()->after('customer_id');

            $table->foreign('daily_voucher_id', 'fk_settlement_credit_sale_payments_daily_voucher_id')
                  ->references('id')->on('daily_vouchers')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->dropForeign('fk_settlement_credit_sale_payments_daily_voucher_id');
            $table->dropColumn('daily_voucher_id');
        });
    }
}

