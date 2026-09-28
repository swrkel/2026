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
        Schema::create('vat_customer_statement_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('statement_id')->index('statement_id')->comment('id form customer statement table');
            $table->date('date');
            $table->string('invoice_no')->nullable();
            $table->string('order_no')->nullable();
            $table->string('product')->nullable();
            $table->decimal('unit_price', 15)->nullable();
            $table->decimal('qty', 15)->nullable();
            $table->string('vehicle_number', 100)->nullable();
            $table->decimal('invoice_amount', 15)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('product_id')->index('product_id');
            $table->integer('transaction_id')->index('transaction_id');
            $table->decimal('unit_price_before_tax', 10, 3);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_customer_statement_details');
    }
};
