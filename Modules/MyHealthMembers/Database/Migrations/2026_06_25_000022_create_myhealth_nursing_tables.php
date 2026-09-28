<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('myhealth_nursing_vital_signs')) {
            Schema::create('myhealth_nursing_vital_signs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('appointment_id')->nullable()->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->unsignedBigInteger('recorded_by')->nullable()->index();
                $table->dateTime('recorded_at')->nullable();
                $table->decimal('temperature', 8, 2)->nullable();
                $table->integer('pulse')->nullable();
                $table->integer('respiration')->nullable();
                $table->integer('systolic_bp')->nullable();
                $table->integer('diastolic_bp')->nullable();
                $table->integer('spo2')->nullable();
                $table->integer('height_feet')->nullable();
                $table->integer('height_inches')->nullable();
                $table->decimal('weight', 10, 2)->nullable();
                $table->decimal('bmi', 10, 2)->nullable();
                $table->decimal('blood_sugar', 10, 2)->nullable();
                $table->integer('pain_scale')->nullable();
                $table->string('status', 30)->default('normal')->index();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_nursing_notes')) {
            Schema::create('myhealth_nursing_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('appointment_id')->nullable()->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->unsignedBigInteger('nurse_id')->nullable()->index();
                $table->string('shift', 20)->nullable()->index();
                $table->string('note_type', 50)->nullable();
                $table->text('observations')->nullable();
                $table->text('doctor_instructions')->nullable();
                $table->text('nursing_actions')->nullable();
                $table->text('medication_notes')->nullable();
                $table->text('escalation_notes')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->dateTime('noted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_medication_administrations')) {
            Schema::create('myhealth_medication_administrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('prescription_id')->nullable()->index();
                $table->unsignedBigInteger('medicine_id')->nullable()->index();
                $table->unsignedBigInteger('nurse_id')->nullable()->index();
                $table->string('medicine_name')->nullable();
                $table->string('dose')->nullable();
                $table->string('route')->nullable();
                $table->dateTime('time_due')->nullable()->index();
                $table->dateTime('time_given')->nullable();
                $table->string('status', 30)->default('due')->index();
                $table->text('missed_reason')->nullable();
                $table->text('adverse_reaction')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_nursing_care_plans')) {
            Schema::create('myhealth_nursing_care_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->unsignedBigInteger('nurse_id')->nullable()->index();
                $table->string('nursing_diagnosis')->nullable();
                $table->text('goals')->nullable();
                $table->text('interventions')->nullable();
                $table->text('outcomes')->nullable();
                $table->date('review_date')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('myhealth_nursing_handovers')) {
            Schema::create('myhealth_nursing_handovers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('from_nurse_id')->nullable()->index();
                $table->unsignedBigInteger('to_nurse_id')->nullable()->index();
                $table->string('from_shift', 20)->nullable();
                $table->string('to_shift', 20)->nullable();
                $table->text('patient_summary')->nullable();
                $table->text('outstanding_tasks')->nullable();
                $table->text('critical_alerts')->nullable();
                $table->text('pending_investigations')->nullable();
                $table->text('pending_medications')->nullable();
                $table->dateTime('handover_at')->nullable();
                $table->string('status', 30)->default('submitted')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_nursing_handovers');
        Schema::dropIfExists('myhealth_nursing_care_plans');
        Schema::dropIfExists('myhealth_medication_administrations');
        Schema::dropIfExists('myhealth_nursing_notes');
        Schema::dropIfExists('myhealth_nursing_vital_signs');
    }
};
