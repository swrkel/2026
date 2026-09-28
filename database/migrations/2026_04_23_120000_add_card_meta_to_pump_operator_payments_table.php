<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pump_operator_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                $table->unsignedInteger('customer_id')->nullable()->after('payment_amount');
            }

            if (! Schema::hasColumn('pump_operator_payments', 'card_type')) {
                $table->string('card_type')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('pump_operator_payments', 'card_number')) {
                $table->string('card_number')->nullable()->after('card_type');
            }

            if (! Schema::hasColumn('pump_operator_payments', 'slip_no')) {
                $table->string('slip_no')->nullable()->after('card_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pump_operator_payments', function (Blueprint $table) {
            $columns = [];

            foreach (['slip_no', 'card_number', 'card_type', 'customer_id'] as $column) {
                if (Schema::hasColumn('pump_operator_payments', $column)) {
                    $columns[] = $column;
                }
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
