<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('bill_no')->index();
            $table->date('bill_date')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->decimal('sub_total', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('net_total', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('balance_amount', 20, 4)->default(0);
            $table->string('payment_status')->default('due')->index();
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bs_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id')->index();
            $table->string('line_type')->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bs_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id')->index();
            $table->string('payment_method')->index();
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 20, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bs_cashier_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->date('settlement_date')->index();
            $table->unsignedBigInteger('cashier_id')->nullable()->index();
            $table->decimal('system_total', 20, 4)->default(0);
            $table->decimal('actual_total', 20, 4)->default(0);
            $table->decimal('shortage_amount', 20, 4)->default(0);
            $table->decimal('excess_amount', 20, 4)->default(0);
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('finalized_by')->nullable()->index();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_cashier_settlements');
        Schema::dropIfExists('bs_bill_payments');
        Schema::dropIfExists('bs_bill_lines');
        Schema::dropIfExists('bs_bills');
    }
};
