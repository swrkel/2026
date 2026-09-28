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
        Schema::create('vat_invoice_payments_2', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invoice_id')->nullable()->index('invoice_id');
            $table->integer('account_id')->index('account_id');
            $table->integer('business_id')->nullable()->index('business_id');
            $table->decimal('amount', 22, 6)->default(0);
            $table->string('method')->nullable();
            $table->string('card_transaction_number')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_name')->nullable();
            $table->dateTime('paid_on')->nullable();
            $table->integer('created_by');
            $table->integer('payment_for')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_invoice_payments_2');
    }
};
