<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function connectionName(): string
    {
        return config('myhealthmembers.central_connection', config('database.default'));
    }

    public function up(): void
    {
        if (! Schema::connection($this->connectionName())->hasTable('myhealth_operation_theatre_rooms')) {
            Schema::connection($this->connectionName())->create('myhealth_operation_theatre_rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('room_code')->index();
                $table->string('room_name');
                $table->string('room_type')->default('general')->index();
                $table->string('floor')->nullable();
                $table->string('status')->default('available')->index();
                $table->text('equipment_notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'room_code'], 'myhealth_ot_room_code_unique');
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_surgery_schedules')) {
            Schema::connection($this->connectionName())->create('myhealth_surgery_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->string('surgery_no')->unique();
                $table->string('procedure_name')->index();
                $table->string('procedure_category')->nullable()->index();
                $table->string('priority')->default('elective')->index();
                $table->string('status')->default('scheduled')->index();
                $table->unsignedBigInteger('theatre_room_id')->nullable()->index();
                $table->unsignedBigInteger('surgeon_id')->nullable()->index();
                $table->unsignedBigInteger('assistant_surgeon_id')->nullable()->index();
                $table->unsignedBigInteger('anaesthetist_id')->nullable()->index();
                $table->unsignedBigInteger('nurse_in_charge_id')->nullable()->index();
                $table->timestamp('scheduled_start_at')->nullable()->index();
                $table->timestamp('scheduled_end_at')->nullable();
                $table->integer('estimated_duration_minutes')->nullable();
                $table->timestamp('actual_start_at')->nullable();
                $table->timestamp('actual_end_at')->nullable();
                $table->text('diagnosis')->nullable();
                $table->text('clinical_notes')->nullable();
                $table->text('special_instructions')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_surgery_checklists')) {
            Schema::connection($this->connectionName())->create('myhealth_surgery_checklists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('surgery_schedule_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->boolean('consent_verified')->default(false);
                $table->boolean('identity_verified')->default(false);
                $table->boolean('procedure_site_marked')->default(false);
                $table->boolean('allergy_checked')->default(false);
                $table->boolean('investigations_completed')->default(false);
                $table->boolean('blood_available')->default(false);
                $table->boolean('anaesthesia_clearance')->default(false);
                $table->boolean('fasting_confirmed')->default(false);
                $table->boolean('equipment_ready')->default(false);
                $table->boolean('implant_available')->default(false);
                $table->boolean('antibiotic_given')->default(false);
                $table->string('checklist_status')->default('pending')->index();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->timestamp('checked_at')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_operative_records')) {
            Schema::connection($this->connectionName())->create('myhealth_operative_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('surgery_schedule_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('operation_no')->unique();
                $table->string('anaesthesia_type')->nullable()->index();
                $table->string('procedure_performed')->index();
                $table->timestamp('incision_time')->nullable();
                $table->timestamp('closure_time')->nullable();
                $table->longText('findings')->nullable();
                $table->longText('procedure_notes')->nullable();
                $table->text('implants_used')->nullable();
                $table->text('consumables_used')->nullable();
                $table->decimal('blood_loss_ml', 10, 2)->nullable();
                $table->boolean('blood_transfusion')->default(false);
                $table->text('complications')->nullable();
                $table->boolean('specimen_sent')->default(false);
                $table->unsignedBigInteger('surgeon_id')->nullable()->index();
                $table->unsignedBigInteger('anaesthetist_id')->nullable()->index();
                $table->unsignedBigInteger('scrub_nurse_id')->nullable()->index();
                $table->unsignedBigInteger('circulating_nurse_id')->nullable()->index();
                $table->string('status')->default('recorded')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_post_operative_notes')) {
            Schema::connection($this->connectionName())->create('myhealth_post_operative_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('surgery_schedule_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('operative_record_id')->nullable()->index();
                $table->string('recovery_status')->default('stable')->index();
                $table->unsignedTinyInteger('pain_score')->nullable();
                $table->string('vital_status')->nullable();
                $table->boolean('icu_transfer_required')->default(false);
                $table->boolean('ward_transfer_required')->default(false);
                $table->longText('post_op_instructions')->nullable();
                $table->longText('medications')->nullable();
                $table->longText('follow_up_plan')->nullable();
                $table->longText('discharge_recommendations')->nullable();
                $table->unsignedBigInteger('noted_by')->nullable()->index();
                $table->timestamp('noted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists('myhealth_post_operative_notes');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_operative_records');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_surgery_checklists');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_surgery_schedules');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_operation_theatre_rooms');
    }
};
