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
        Schema::create('unload_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->dateTime('date_and_time');
            $table->integer('pump_operator_id')->index('pump_operator_id');
            $table->unsignedInteger('tank_id')->index('tank_id');
            $table->integer('product_id')->index('product_id');
            $table->string('product');
            $table->decimal('unloaded_qty', 15, 4);
            $table->string('bill_no');
            $table->decimal('dip_reading', 15, 4);
            $table->decimal('current_stock', 15, 4);
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unload_stocks');
    }
};
