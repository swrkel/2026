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
        if (! Schema::connection($this->connectionName())->hasTable('myhealth_radiology_requests')) {
            Schema::connection($this->connectionName())->create('myhealth_radiology_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('consultation_id')->nullable()->index();
                $table->unsignedBigInteger('appointment_id')->nullable()->index();
                $table->string('request_no')->unique();
                $table->string('study_type')->index();
                $table->string('modality')->index();
                $table->string('body_part')->nullable()->index();
                $table->text('clinical_notes')->nullable();
                $table->string('priority')->default('routine')->index();
                $table->string('status')->default('requested')->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('performed_at')->nullable();
                $table->timestamp('reported_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->unsignedBigInteger('technician_id')->nullable()->index();
                $table->unsignedBigInteger('radiologist_id')->nullable()->index();
                $table->string('equipment_name')->nullable();
                $table->string('room_no')->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_radiology_reports')) {
            Schema::connection($this->connectionName())->create('myhealth_radiology_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('radiology_request_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('report_no')->unique();
                $table->longText('findings');
                $table->longText('impression')->nullable();
                $table->longText('recommendations')->nullable();
                $table->boolean('critical_finding')->default(false)->index();
                $table->text('critical_notes')->nullable();
                $table->string('status')->default('reported')->index();
                $table->unsignedBigInteger('reported_by')->nullable()->index();
                $table->unsignedBigInteger('verified_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->unsignedBigInteger('released_by')->nullable()->index();
                $table->timestamp('reported_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection($this->connectionName())->hasTable('myhealth_radiology_attachments')) {
            Schema::connection($this->connectionName())->create('myhealth_radiology_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('radiology_request_id')->index();
                $table->unsignedBigInteger('radiology_report_id')->nullable()->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->string('attachment_type')->default('image');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists('myhealth_radiology_attachments');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_radiology_reports');
        Schema::connection($this->connectionName())->dropIfExists('myhealth_radiology_requests');
    }
};
