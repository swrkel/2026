<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkg_ib_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('internet_customer_id')->index();
            $table->string('payment_no')->unique();
            $table->string('biller_category')->nullable()->index();
            $table->string('biller_name')->nullable();
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('charge_amount', 22, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->date('scheduled_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkg_ib_bill_payments');
    }
};
