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
        Schema::create('vat_meter_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settlement_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('pump_id')->index('pump_id');
            $table->decimal('starting_meter', 15, 5);
            $table->decimal('closing_meter', 15, 5);
            $table->decimal('price', 22, 5);
            $table->decimal('qty', 22, 5);
            $table->string('discount')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('testing_qty', 15)->nullable();
            $table->decimal('sub_total', 15, 5);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedInteger('transaction_id')->nullable()->index('transaction_id');
            $table->decimal('meter_reset_value', 15, 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_meter_sales');
    }
};
