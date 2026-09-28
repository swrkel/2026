<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'petro_settlement_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('petro_settlement_id')->nullable()->index()->after('id');
            });
        }

        if (Schema::hasTable('pump_operator_meter_sales') && ! Schema::hasColumn('pump_operator_meter_sales', 'settlement_no')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->string('settlement_no', 255)->nullable()->index()->after('collection_form_no');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('pump_operator_meter_sales') && Schema::hasColumn('pump_operator_meter_sales', 'settlement_no')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->dropColumn('settlement_no');
            });
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'petro_settlement_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('petro_settlement_id');
            });
        }
    }
};
