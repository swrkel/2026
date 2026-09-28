<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyhealthVaccinationTables extends Migration
{
    public function up()
    {
        Schema::create('myhealth_vaccines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('vaccine_code', 50)->index();
            $table->string('vaccine_name');
            $table->string('manufacturer')->nullable();
            $table->string('vaccine_type', 50)->nullable()->index();
            $table->string('dose_schedule')->nullable();
            $table->string('storage_temperature')->nullable();
            $table->integer('default_interval_days')->nullable();
            $table->boolean('booster_required')->default(false);
            $table->integer('booster_interval_days')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('myhealth_vaccine_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('vaccine_id')->index();
            $table->string('batch_no')->index();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('received_qty', 15, 4)->default(0);
            $table->decimal('used_qty', 15, 4)->default(0);
            $table->decimal('available_qty', 15, 4)->default(0);
            $table->string('supplier')->nullable();
            $table->string('purchase_reference')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('myhealth_vaccination_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->unsignedBigInteger('vaccine_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('vaccination_no', 50)->unique();
            $table->integer('dose_no')->nullable();
            $table->date('date_given')->index();
            $table->date('next_due_date')->nullable()->index();
            $table->string('administered_by')->nullable();
            $table->string('administered_location')->nullable();
            $table->string('status', 30)->default('given')->index();
            $table->string('adverse_reaction')->nullable();
            $table->text('reaction_notes')->nullable();
            $table->string('certificate_no')->nullable()->index();
            $table->timestamp('certificate_issued_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('myhealth_immunization_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('schedule_name');
            $table->string('schedule_type', 50)->index();
            $table->unsignedBigInteger('vaccine_id')->index();
            $table->integer('dose_no')->nullable();
            $table->integer('recommended_age_days')->nullable();
            $table->string('recommended_age_text')->nullable();
            $table->integer('interval_days')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('myhealth_vaccine_adverse_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('vaccination_record_id')->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->string('severity', 30)->nullable()->index();
            $table->date('event_date')->nullable()->index();
            $table->text('symptoms')->nullable();
            $table->text('action_taken')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->string('reporting_status', 30)->default('pending')->index();
            $table->string('reported_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('myhealth_vaccine_adverse_events');
        Schema::dropIfExists('myhealth_immunization_schedules');
        Schema::dropIfExists('myhealth_vaccination_records');
        Schema::dropIfExists('myhealth_vaccine_batches');
        Schema::dropIfExists('myhealth_vaccines');
    }
}
