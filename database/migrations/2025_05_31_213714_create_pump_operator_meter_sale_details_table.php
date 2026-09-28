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
        Schema::create('pump_operator_meter_sale_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sale_id')->index('sale_id');
            $table->integer('business_id')->index('business_id');
            $table->integer('pump_operator_id')->index('pump_operator_id');
            $table->integer('pump_id')->index('pump_id');
            $table->decimal('received_meter', 16, 5);
            $table->decimal('new_meter', 16, 5);
            $table->decimal('sold_qty', 16, 5);
            $table->decimal('unit_price', 16, 3);
            $table->decimal('amount', 16, 3);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pump_operator_meter_sale_details');
    }
};
