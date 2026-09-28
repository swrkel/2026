<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_receptions')) {
            Schema::create('auto_service_receptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->string('reception_no')->unique();
                $table->dateTime('received_at')->nullable()->index();
                $table->unsignedInteger('odometer')->nullable();
                $table->string('fuel_level')->nullable();
                $table->text('customer_complaint')->nullable();
                $table->text('advisor_remarks')->nullable();
                $table->text('accessories_received')->nullable();
                $table->text('existing_damage')->nullable();
                $table->string('status')->default('received')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::table('auto_service_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('auto_service_jobs', 'technician_id')) $table->unsignedBigInteger('technician_id')->nullable()->after('status')->index();
            if (!Schema::hasColumn('auto_service_jobs', 'service_bay')) $table->string('service_bay')->nullable()->after('technician_id');
            if (!Schema::hasColumn('auto_service_jobs', 'priority')) $table->string('priority')->default('normal')->after('service_bay')->index();
            if (!Schema::hasColumn('auto_service_jobs', 'workflow_stage')) $table->string('workflow_stage')->default('received')->after('priority')->index();
        });
    }
    public function down()
    {
        Schema::dropIfExists('auto_service_receptions');
    }
};
