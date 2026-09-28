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
        Schema::create('daily_cheque_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('linked_payment_id', 10)->nullable()->index('linked_payment_id');
            $table->string('settlement_no', 255)->nullable();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->string('bank_name', 255);
            $table->string('cheque_number', 255);
            $table->date('cheque_date');
            $table->decimal('amount', 15, 6);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('shift_id')->index('shift_id');
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
        Schema::dropIfExists('daily_cheque_payments');
    }
};
