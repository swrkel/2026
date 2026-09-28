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
        Schema::create('vat_payments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('contact_id');
            $table->string('form_no', 256)->nullable();
            $table->date('date');
            $table->decimal('amount', 10);
            $table->integer('payable_account_id');
            $table->string('payment_method', 256);
            $table->integer('payment_account_id');
            $table->string('cheque_number', 256)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('to_account_no', 256)->nullable();
            $table->string('recipient_name', 256)->nullable();
            $table->text('note')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_payments');
    }
};
