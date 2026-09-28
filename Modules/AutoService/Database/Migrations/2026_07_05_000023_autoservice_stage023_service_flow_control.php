<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('auto_service_job_mechanics')) {
            Schema::table('auto_service_job_mechanics', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_job_mechanics', 'location_id')) $table->unsignedBigInteger('location_id')->nullable()->after('business_id')->index();
                if (!Schema::hasColumn('auto_service_job_mechanics', 'quality_note')) $table->text('quality_note')->nullable()->after('note');
            });
        }

        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs', 'service_flow_locked')) $table->tinyInteger('service_flow_locked')->default(0)->after('job_progress')->index();
                if (!Schema::hasColumn('auto_service_jobs', 'ready_for_qc_at')) $table->dateTime('ready_for_qc_at')->nullable()->after('service_flow_locked');
            });
        }
    }

    public function down()
    {
        // Columns intentionally retained for tenant history safety.
    }
};
