<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('rcm_customer_payments')) {
            Schema::create('rcm_customer_payments', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->string('payment_no',40);
                $t->unsignedBigInteger('customer_id')->index();
                $t->dateTime('payment_date')->index();
                $t->decimal('amount',20,4)->default(0);
                $t->decimal('allocated_amount',20,4)->default(0);
                $t->decimal('advance_amount',20,4)->default(0);
                $t->string('method',40)->default('cash')->index();
                $t->string('reference_no',100)->nullable();
                $t->string('cheque_number',100)->nullable();
                $t->string('bank_name',150)->nullable();
                $t->text('note')->nullable();
                $t->string('status',20)->default('posted')->index();
                $t->text('reversal_note')->nullable();
                $t->unsignedBigInteger('reversed_by')->nullable();
                $t->timestamp('reversed_at')->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->unique(['business_id','payment_no'],'rcm_customer_payment_no_unique');
                $t->index(['business_id','customer_id','payment_date'],'rcm_customer_payment_customer_date_idx');
            });
        }

        if (! Schema::hasTable('rcm_customer_payment_allocations')) {
            Schema::create('rcm_customer_payment_allocations', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('business_id')->index();
                $t->unsignedBigInteger('payment_id')->index();
                $t->unsignedBigInteger('dispatch_id')->index();
                $t->decimal('amount',20,4)->default(0);
                $t->timestamps();
                $t->unique(['payment_id','dispatch_id'],'rcm_customer_payment_allocation_unique');
                $t->index(['business_id','dispatch_id'],'rcm_customer_payment_dispatch_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rcm_customer_payment_allocations');
        Schema::dropIfExists('rcm_customer_payments');
    }
};
