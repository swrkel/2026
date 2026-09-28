<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('myhealth_hospital_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('department_code', 30)->nullable()->index();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('myhealth_consultation_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('room_code', 30)->nullable()->index();
            $table->string('room_name');
            $table->string('floor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('myhealth_doctor_session_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('day_of_week', 20)->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('consultation_duration_minutes')->default(15);
            $table->unsignedInteger('maximum_patients')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('myhealth_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('appointment_no', 30)->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->date('appointment_date')->index();
            $table->time('appointment_time')->nullable();
            $table->string('queue_no', 30)->nullable()->index();
            $table->string('token_no', 30)->nullable()->index();
            $table->string('visit_type', 30)->default('opd');
            $table->string('status', 30)->default('waiting')->index();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_appointments');
        Schema::dropIfExists('myhealth_doctor_session_schedules');
        Schema::dropIfExists('myhealth_consultation_rooms');
        Schema::dropIfExists('myhealth_hospital_departments');
    }
};
