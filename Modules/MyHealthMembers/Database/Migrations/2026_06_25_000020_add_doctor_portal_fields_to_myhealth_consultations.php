<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDoctorPortalFieldsToMyhealthConsultations extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->table('myhealth_consultations', function (Blueprint $table) {
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'history_present_illness')) {
                $table->text('history_present_illness')->nullable()->after('chief_complaint');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'vital_signs')) {
                $table->text('vital_signs')->nullable()->after('history_present_illness');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'examination_notes')) {
                $table->text('examination_notes')->nullable()->after('vital_signs');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'investigation_plan')) {
                $table->text('investigation_plan')->nullable()->after('examination_notes');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'treatment_plan')) {
                $table->text('treatment_plan')->nullable()->after('investigation_plan');
            }
            if (!Schema::connection($connection)->hasColumn('myhealth_consultations', 'follow_up_date')) {
                $table->date('follow_up_date')->nullable()->after('treatment_plan');
            }
        });

        Schema::connection($connection)->table('myhealth_diagnoses', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('myhealth_diagnoses', 'consultation_id')) {
                $table->unsignedBigInteger('consultation_id')->nullable()->index()->after('doctor_user_id');
            }
        });

        Schema::connection($connection)->table('myhealth_prescriptions', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('myhealth_prescriptions', 'consultation_id')) {
                $table->unsignedBigInteger('consultation_id')->nullable()->index()->after('doctor_user_id');
            }
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->table('myhealth_consultations', function (Blueprint $table) {
            foreach (['history_present_illness', 'vital_signs', 'examination_notes', 'investigation_plan', 'treatment_plan', 'follow_up_date'] as $column) {
                if (Schema::connection($connection)->hasColumn('myhealth_consultations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::connection($connection)->table('myhealth_diagnoses', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('myhealth_diagnoses', 'consultation_id')) {
                $table->dropColumn('consultation_id');
            }
        });

        Schema::connection($connection)->table('myhealth_prescriptions', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('myhealth_prescriptions', 'consultation_id')) {
                $table->dropColumn('consultation_id');
            }
        });
    }
}

