<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected function connectionName(): ?string
    {
        return config('myhealthmembers.central_connection');
    }

    public function up(): void
    {
        Schema::connection($this->connectionName())->table('myhealth_business_permissions', function (Blueprint $table) {
            if (!Schema::connection($this->connectionName())->hasColumn('myhealth_business_permissions', 'can_access_telemedicine')) {
                $table->boolean('can_access_telemedicine')->default(false)->after('can_manage_claims');
            }
            if (!Schema::connection($this->connectionName())->hasColumn('myhealth_business_permissions', 'can_manage_telemedicine')) {
                $table->boolean('can_manage_telemedicine')->default(false)->after('can_access_telemedicine');
            }
        });

        Schema::connection($this->connectionName())->create('myhealth_doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('slot_minutes')->default(15);
            $table->string('consultation_mode')->default('video');
            $table->decimal('consultation_fee', 22, 4)->default(0);
            $table->string('status')->default('available');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'schedule_date', 'status'], 'mh_doc_schedules_doc_date_status_idx');
        });

        Schema::connection($this->connectionName())->create('myhealth_telemedicine_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_no')->unique();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('consultation_mode')->default('video');
            $table->decimal('consultation_fee', 22, 4)->default(0);
            $table->string('status')->default('booked');
            $table->string('consent_status')->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['member_id', 'appointment_date'], 'mh_tel_appt_member_date_idx');
            $table->index(['doctor_id', 'appointment_date', 'status'], 'mh_tel_appt_doctor_date_status_idx');
        });

        Schema::connection($this->connectionName())->create('myhealth_telemedicine_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('session_token')->unique();
            $table->string('meeting_url')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('status')->default('waiting');
            $table->text('doctor_notes')->nullable();
            $table->timestamps();
            $table->index(['appointment_id', 'status'], 'mh_tel_sessions_appt_status_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists('myhealth_telemedicine_sessions');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_telemedicine_appointments');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_doctor_schedules');
    }
};
