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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('customer_payment_no')->nullable();
            $table->string('settlement_no');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->string('payment_method');
            $table->dateTime('cheque_date')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('amount', 15, 5);
            $table->decimal('sub_total', 22, 6);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('post_dated_cheque')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_payments');
    }
};
