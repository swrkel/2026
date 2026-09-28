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
        Schema::create('stock_conversions', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->string('conversion_form_no', 30);
            $table->string('product_convert_from', 30);
            $table->string('unit_convert_from', 20);
            $table->integer('total_qty_convert_from');
            $table->string('product_convert_to', 20);
            $table->string('unit_convert_to', 20);
            $table->integer('qty_convert_to');
            $table->string('location', 50);
            $table->string('user', 50);
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
        Schema::dropIfExists('stock_conversions');
    }
};
