<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rcm_operational_payments')) {
            return;
        }

        Schema::create('rcm_operational_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->string('source_type',60);
            $table->unsignedBigInteger('source_id');
            $table->string('payment_context',30);
            $table->string('payment_method',60);
            $table->string('payment_method_label',120)->nullable();
            $table->unsignedBigInteger('payment_account_id');
            $table->string('payment_account_name',191)->nullable();
            $table->decimal('amount',20,4)->default(0);
            $table->text('note')->nullable();
            $table->string('event_type',80);
            $table->json('meta')->nullable();
            $table->string('status',30)->default('pending')->index();
            $table->unsignedBigInteger('finance_outbox_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id','source_type','source_id'],'rcm_operational_payment_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rcm_operational_payments');
    }
};
