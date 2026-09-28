<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_appointments')) {
            Schema::create('auto_service_appointments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('appointment_no')->unique();
                $table->dateTime('appointment_at')->nullable()->index();
                $table->string('service_type')->nullable();
                $table->string('status')->default('scheduled')->index();
                $table->string('request_source')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_mobile', 50)->nullable();
                $table->string('customer_email')->nullable();
                $table->text('customer_note')->nullable();
                $table->text('internal_note')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_estimates')) {
            Schema::create('auto_service_estimates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->string('estimate_no')->unique();
                $table->date('estimate_date')->nullable()->index();
                $table->date('valid_until')->nullable();
                $table->string('status')->default('draft')->index();
                $table->text('customer_complaint')->nullable();
                $table->text('advisor_notes')->nullable();
                $table->decimal('subtotal', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->dateTime('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_estimate_lines')) {
            Schema::create('auto_service_estimate_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('estimate_id')->index();
                $table->string('line_type')->default('service');
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('description');
                $table->decimal('quantity', 22, 4)->default(1);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_service_inspections')) {
            Schema::create('auto_service_inspections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->string('inspection_no')->unique();
                $table->date('inspection_date')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->unsignedInteger('odometer')->nullable();
                $table->string('fuel_level')->nullable();
                $table->text('customer_remarks')->nullable();
                $table->text('advisor_remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_inspection_items')) {
            Schema::create('auto_service_inspection_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('inspection_id')->index();
                $table->string('section')->nullable();
                $table->string('item_name');
                $table->string('condition')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_inspection_items');
        Schema::dropIfExists('auto_service_inspections');
        Schema::dropIfExists('auto_service_estimate_lines');
        Schema::dropIfExists('auto_service_estimates');
        Schema::dropIfExists('auto_service_appointments');
    }
};
