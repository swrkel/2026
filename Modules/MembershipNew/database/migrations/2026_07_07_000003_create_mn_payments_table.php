<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mn_payments')) {
            return;
        }

        Schema::create('mn_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('plan_id')->nullable()->index();
            $table->date('payment_date');
            $table->string('payment_ref_no', 100)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('member_id')->references('id')->on('mn_members')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('mn_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mn_payments');
    }
};
