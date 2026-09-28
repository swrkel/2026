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
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cash_register_id')->index('cash_register_id');
            $table->decimal('amount', 22, 4)->default(0);
            $table->enum('pay_method', ['cash', 'card', 'cheque', 'bank_transfer', 'custom_pay_1', 'custom_pay_2', 'custom_pay_3', 'other'])->nullable();
            $table->enum('type', ['debit', 'credit']);
            $table->enum('transaction_type', ['initial', 'sell', 'transfer', 'refund']);
            $table->integer('transaction_id')->nullable()->index();
            $table->timestamps();

            $table->index(['cash_register_id'], 'cash_register_transactions_cash_register_id_foreign');
            $table->index(['transaction_id'], 'transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};
