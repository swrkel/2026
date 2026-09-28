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
        Schema::create('settlement_cheque_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('settlement_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->string('bank_name', 255);
            $table->string('cheque_number', 255);
            $table->date('cheque_date');
            $table->decimal('amount', 15, 6);
            $table->unsignedInteger('customer_payment_id')->nullable()->index('customer_payment_id');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('settlement_cheque_payments');
    }
};
