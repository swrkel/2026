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
        Schema::create('form_f22_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('header_id')->index('header_id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('form_no');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('product_code', 499)->nullable();
            $table->text('product')->nullable();
            $table->string('book_no')->nullable();
            $table->decimal('current_stock', 15)->nullable();
            $table->decimal('stock_count', 15)->nullable();
            $table->decimal('unit_purchase_price', 15)->nullable();
            $table->decimal('unit_sale_price', 15)->nullable();
            $table->decimal('purchase_price_total', 15)->nullable();
            $table->decimal('sales_price_total', 15)->nullable();
            $table->decimal('difference_qty', 15)->nullable();
            $table->integer('status')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('difference_value', 10, 5);
            $table->integer('debit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f22_details');
    }
};
