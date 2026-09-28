<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_mechanics')) {
            Schema::create('auto_service_mechanics', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('mechanic_code')->nullable()->index();
                $table->string('name');
                $table->string('mobile')->nullable();
                $table->string('speciality')->nullable();
                $table->decimal('hourly_rate',22,4)->default(0);
                $table->tinyInteger('is_active')->default(1)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_job_mechanics')) {
            Schema::create('auto_service_job_mechanics', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->unsignedBigInteger('mechanic_id')->index();
                $table->dateTime('assigned_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->decimal('estimated_hours',10,2)->default(0);
                $table->decimal('actual_hours',10,2)->default(0);
                $table->string('status')->default('assigned')->index();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_service_service_packages')) {
            Schema::create('auto_service_service_packages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('package_code')->nullable()->index();
                $table->string('name');
                $table->string('vehicle_type')->nullable();
                $table->decimal('labour_amount',22,4)->default(0);
                $table->decimal('parts_amount',22,4)->default(0);
                $table->decimal('total_amount',22,4)->default(0);
                $table->tinyInteger('is_active')->default(1)->index();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_package_lines')) {
            Schema::create('auto_service_package_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('package_id')->index();
                $table->string('line_type')->default('labour');
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('description');
                $table->decimal('quantity',22,4)->default(1);
                $table->decimal('unit_price',22,4)->default(0);
                $table->decimal('line_total',22,4)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_service_part_movements')) {
            Schema::create('auto_service_part_movements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('movement_type')->default('reserved')->index();
                $table->string('description')->nullable();
                $table->decimal('quantity',22,4)->default(0);
                $table->decimal('unit_cost',22,4)->default(0);
                $table->decimal('line_total',22,4)->default(0);
                $table->date('movement_date')->nullable()->index();
                $table->string('reference_no')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs','work_started_at')) $table->dateTime('work_started_at')->nullable()->after('estimated_delivery_at');
                if (!Schema::hasColumn('auto_service_jobs','work_completed_at')) $table->dateTime('work_completed_at')->nullable()->after('work_started_at');
                if (!Schema::hasColumn('auto_service_jobs','quality_checked_at')) $table->dateTime('quality_checked_at')->nullable()->after('work_completed_at');
                if (!Schema::hasColumn('auto_service_jobs','ready_at')) $table->dateTime('ready_at')->nullable()->after('quality_checked_at');
                if (!Schema::hasColumn('auto_service_jobs','delivered_at')) $table->dateTime('delivered_at')->nullable()->after('ready_at');
                if (!Schema::hasColumn('auto_service_jobs','delivery_note')) $table->text('delivery_note')->nullable()->after('delivered_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_part_movements');
        Schema::dropIfExists('auto_service_package_lines');
        Schema::dropIfExists('auto_service_service_packages');
        Schema::dropIfExists('auto_service_job_mechanics');
        Schema::dropIfExists('auto_service_mechanics');
    }
};
