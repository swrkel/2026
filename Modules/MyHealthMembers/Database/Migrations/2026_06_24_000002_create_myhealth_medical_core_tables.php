<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthMedicalCoreTables extends Migration
{
    public function up(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->create('myhealth_doctors', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_code')->unique();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('name');
            $table->string('registration_no')->nullable()->index();
            $table->string('specialization')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_no')->unique();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('doctor_id')->nullable()->index();
            $table->unsignedBigInteger('doctor_user_id')->nullable()->index();
            $table->date('consultation_date');
            $table->time('consultation_time')->nullable();
            $table->string('visit_type')->nullable();
            $table->string('status')->default('open');
            $table->text('chief_complaint')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
        });

        Schema::connection($connection)->create('myhealth_member_consents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('section')->index();
            $table->string('access_type')->default('view');
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'business_id', 'section']);
        });

        Schema::connection($connection)->create('myhealth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('section')->nullable();
            $table->string('action');
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->dropIfExists('myhealth_audit_logs');
        Schema::connection($connection)->dropIfExists('myhealth_member_consents');
        Schema::connection($connection)->dropIfExists('myhealth_consultations');
        Schema::connection($connection)->dropIfExists('myhealth_doctors');
    }
}
