<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleServiceJobsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('vehicle_service_jobs')) {
            Schema::create('vehicle_service_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('job_no')->nullable()->index();
                $table->date('transaction_date')->nullable()->index();
                $table->unsignedInteger('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->string('customer_mobile')->nullable();
                $table->string('vehicle_no')->nullable()->index();
                $table->string('vehicle_make')->nullable();
                $table->string('vehicle_model')->nullable();
                $table->string('meter_reading')->nullable();
                $table->text('service_notes')->nullable();
                $table->decimal('subtotal', 22, 4)->default(0);
                $table->decimal('discount_total', 22, 4)->default(0);
                $table->decimal('tax_total', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->decimal('paid_amount', 22, 4)->default(0);
                $table->decimal('balance_amount', 22, 4)->default(0);
                $table->string('payment_type')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->unsignedInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vehicle_service_jobs');
    }
}
