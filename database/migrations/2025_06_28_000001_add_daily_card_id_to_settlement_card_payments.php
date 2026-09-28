<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDailyCardIdToSettlementCardPayments extends Migration
{
    public function up()
    {
        Schema::table('settlement_card_payments', function (Blueprint $table) {
            $table->unsignedInteger('daily_card_id')->nullable()->after('customer_id');

            $table->foreign('daily_card_id', 'fk_settlement_card_daily_card_id')
                  ->references('id')->on('daily_cards')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('settlement_card_payments', function (Blueprint $table) {
            $table->dropForeign('fk_settlement_card_daily_card_id');
            $table->dropColumn('daily_card_id');
        });
    }
}
