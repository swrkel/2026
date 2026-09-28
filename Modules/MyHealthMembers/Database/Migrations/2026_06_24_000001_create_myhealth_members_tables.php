<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthMembersTables extends Migration
{
    public function up(): void
    {
        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_members', function (Blueprint $table) {
            $table->id();
            $table->string('myhealth_code')->unique();
            $table->string('name');
            $table->string('mobile')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('nic_no')->nullable()->index();
            $table->string('passport_no')->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_mobile')->nullable();
            $table->string('registered_source')->default('business'); // self | business
            $table->unsignedBigInteger('registered_business_id')->nullable();
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['registered_business_id', 'is_active']);
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_member_logins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('login_code')->unique();
            $table->string('password')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('myhealth_members')->cascadeOnDelete();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_business_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->boolean('can_register_member')->default(false);
            $table->boolean('can_view_profile')->default(false);
            $table->boolean('can_edit_profile')->default(false);
            $table->boolean('can_view_medical_history')->default(false);
            $table->boolean('can_create_diagnosis')->default(false);
            $table->boolean('can_create_prescription')->default(false);
            $table->boolean('can_upload_documents')->default(false);
            $table->boolean('can_view_documents')->default(false);
            $table->boolean('can_export_print')->default(false);
            $table->date('access_expiry_date')->nullable();
            $table->timestamps();

            $table->unique('business_id');
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_access_passcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('passcode');
            $table->string('purpose')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('myhealth_members')->cascadeOnDelete();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('section');
            $table->string('action');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accessed_at');
            $table->timestamps();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('past_surgeries')->nullable();
            $table->text('family_history')->nullable();
            $table->unsignedBigInteger('updated_by_business_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('doctor_user_id')->nullable();
            $table->date('diagnosis_date');
            $table->string('title')->nullable();
            $table->text('symptoms')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('doctor_user_id')->nullable();
            $table->date('prescription_date');
            $table->text('prescription_details');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::connection(config('myhealthmembers.central_connection'))->create('myhealth_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('document_type')->nullable();
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('myhealthmembers.central_connection');

        Schema::connection($connection)->dropIfExists('myhealth_documents');
        Schema::connection($connection)->dropIfExists('myhealth_prescriptions');
        Schema::connection($connection)->dropIfExists('myhealth_diagnoses');
        Schema::connection($connection)->dropIfExists('myhealth_medical_histories');
        Schema::connection($connection)->dropIfExists('myhealth_access_logs');
        Schema::connection($connection)->dropIfExists('myhealth_access_passcodes');
        Schema::connection($connection)->dropIfExists('myhealth_business_permissions');
        Schema::connection($connection)->dropIfExists('myhealth_member_logins');
        Schema::connection($connection)->dropIfExists('myhealth_members');
    }
}
