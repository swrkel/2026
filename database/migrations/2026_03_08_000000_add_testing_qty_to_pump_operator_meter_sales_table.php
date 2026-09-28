<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds testing_qty to pump_operator_meter_sales so Settlement PD Edit/View/Print can display it.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pump_operator_meter_sales') && ! Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->decimal('testing_qty', 15)->nullable()->after('p_o_payment_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            Schema::table('pump_operator_meter_sales', function (Blueprint $table) {
                $table->dropColumn('testing_qty');
            });
        }
    }
};
