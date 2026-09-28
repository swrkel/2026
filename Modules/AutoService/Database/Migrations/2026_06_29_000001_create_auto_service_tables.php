<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('auto_service_vehicles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->string('registration_no')->index();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('year')->nullable();
            $table->string('vin')->nullable()->index();
            $table->string('engine_no')->nullable();
            $table->string('chassis_no')->nullable();
            $table->unsignedInteger('current_odometer')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('next_service_odometer')->nullable();
            $table->text('notes')->nullable();
            $table->tinyInteger('is_active')->default(1)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('auto_service_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->string('job_no')->unique();
            $table->date('job_date')->nullable();
            $table->string('job_type')->nullable();
            $table->unsignedInteger('odometer')->nullable();
            $table->string('status')->default('received')->index();
            $table->dateTime('estimated_delivery_at')->nullable();
            $table->text('customer_complaint')->nullable();
            $table->text('advisor_notes')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->decimal('paid_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('next_service_odometer')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('auto_service_job_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->string('line_type')->default('service');
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('description');
            $table->decimal('quantity', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('auto_service_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_service_timeline', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('event_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('auto_service_reminders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->date('due_date')->index();
            $table->integer('days_before')->default(7);
            $table->date('send_on')->index();
            $table->string('channel')->default('sms');
            $table->string('mobile')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_service_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_settings');
        Schema::dropIfExists('auto_service_reminders');
        Schema::dropIfExists('auto_service_timeline');
        Schema::dropIfExists('auto_service_payments');
        Schema::dropIfExists('auto_service_job_lines');
        Schema::dropIfExists('auto_service_jobs');
        Schema::dropIfExists('auto_service_vehicles');
    }
};
