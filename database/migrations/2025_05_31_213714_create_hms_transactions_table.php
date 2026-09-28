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
        Schema::create('hms_transactions', function (Blueprint $table) {
            $table->integer('id')->nullable();
            $table->integer('booking_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->string('payment_account', 200)->nullable();
            $table->integer('sales_income_account_id')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('description', 200)->nullable();
            $table->string('amount', 200)->nullable();
            $table->string('cheque_number', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hms_transactions');
    }
};
