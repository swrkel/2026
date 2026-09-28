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
        Schema::create('settlement_credit_sale_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settlement_no', 255)->nullable();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->string('order_number', 255);
            $table->date('order_date');
            $table->string('customer_reference')->nullable();
            $table->decimal('price', 15, 6);
            $table->decimal('discount', 10);
            $table->decimal('total_discount', 15, 3);
            $table->decimal('sub_total', 15, 3);
            $table->decimal('qty', 15, 6);
            $table->decimal('amount', 15, 6);
            $table->decimal('outstanding', 15, 6)->nullable();
            $table->decimal('credit_limit', 15, 6)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('transaction_id')->nullable()->index('transaction_id');
            $table->integer('is_from_pumper')->default(0);
            $table->integer('pump_operator_id')->nullable()->index('pump_operator_id');
            $table->integer('is_committed')->nullable();
            $table->string('collection_form_no', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settlement_credit_sale_payments');
    }
};
