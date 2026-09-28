<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_quality_checks')) {
            Schema::create('auto_service_quality_checks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('qc_no')->nullable()->index();
                $table->date('qc_date')->nullable()->index();
                $table->string('status')->default('pending')->index();
                $table->tinyInteger('mechanical_checked')->default(0);
                $table->tinyInteger('electrical_checked')->default(0);
                $table->tinyInteger('road_test_done')->default(0);
                $table->tinyInteger('wash_done')->default(0);
                $table->tinyInteger('customer_concern_verified')->default(0);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->dateTime('checked_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_deliveries')) {
            Schema::create('auto_service_deliveries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('delivery_no')->nullable()->index();
                $table->dateTime('delivered_at')->nullable()->index();
                $table->string('status')->default('pending')->index();
                $table->decimal('outstanding_amount', 22, 4)->default(0);
                $table->tinyInteger('invoice_confirmed')->default(0);
                $table->tinyInteger('payment_confirmed')->default(0);
                $table->tinyInteger('vehicle_handover_confirmed')->default(0);
                $table->text('customer_signature')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('delivered_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs', 'qc_status')) $table->string('qc_status')->nullable()->after('workflow_stage')->index();
                if (!Schema::hasColumn('auto_service_jobs', 'delivery_status')) $table->string('delivery_status')->nullable()->after('qc_status')->index();
                if (!Schema::hasColumn('auto_service_jobs', 'job_progress')) $table->unsignedTinyInteger('job_progress')->default(0)->after('delivery_status');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_deliveries');
        Schema::dropIfExists('auto_service_quality_checks');
    }
};
