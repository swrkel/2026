<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('rcm_purchase_payments')) {
            Schema::create('rcm_purchase_payments', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->unsignedBigInteger('purchase_id');
                $t->date('payment_date')->index();
                $t->string('payment_method',60);
                $t->string('payment_method_label',120)->nullable();
                $t->unsignedBigInteger('payable_account_id')->index();
                $t->unsignedBigInteger('payment_account_id')->index();
                $t->decimal('amount',20,4);
                $t->string('cheque_number',120)->nullable();
                $t->text('note')->nullable();
                $t->unsignedBigInteger('debit_account_transaction_id')->nullable()->index();
                $t->unsignedBigInteger('credit_account_transaction_id')->nullable()->index();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->unique(['business_id','purchase_id'],'rcm_purchase_payment_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rcm_purchase_payments');
    }
};
