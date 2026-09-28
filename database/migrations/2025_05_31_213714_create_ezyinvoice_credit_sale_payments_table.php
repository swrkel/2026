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
        Schema::create('ezyinvoice_credit_sale_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoice_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->string('order_number', 255);
            $table->date('order_date');
            $table->string('customer_reference')->nullable();
            $table->decimal('price', 15, 6);
            $table->decimal('discount', 10);
            $table->decimal('qty', 15, 6);
            $table->decimal('amount', 15, 6);
            $table->decimal('outstanding', 15, 6)->nullable();
            $table->decimal('credit_limit', 15, 6)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('transaction_id')->nullable()->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ezyinvoice_credit_sale_payments');
    }
};
