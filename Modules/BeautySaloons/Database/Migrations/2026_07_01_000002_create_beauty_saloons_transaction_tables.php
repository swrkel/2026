<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('appointment_no')->nullable()->index();
            $table->date('appointment_date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('booked')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_appointment_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->index();
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->integer('duration_minutes')->default(0);
            $table->decimal('price', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('bs_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->decimal('selling_price', 22, 4)->default(0);
            $table->decimal('stock_qty', 22, 4)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('bs_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->string('invoice_no')->nullable()->index();
            $table->date('transaction_date')->index();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('tax', 22, 4)->default(0);
            $table->decimal('net_total', 22, 4)->default(0);
            $table->string('payment_status')->default('due')->index();
            $table->timestamps();
        });

        Schema::create('bs_sale_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->index();
            $table->string('line_type')->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('bs_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->index();
            $table->string('method')->index();
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_payments');
        Schema::dropIfExists('bs_sale_lines');
        Schema::dropIfExists('bs_sales');
        Schema::dropIfExists('bs_products');
        Schema::dropIfExists('bs_appointment_services');
        Schema::dropIfExists('bs_appointments');
    }
};
