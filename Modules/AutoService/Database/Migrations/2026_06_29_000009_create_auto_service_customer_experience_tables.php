<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('auto_service_documents')) {
            Schema::create('auto_service_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('document_type')->default('general')->index();
                $table->string('title')->nullable();
                $table->string('file_name')->nullable();
                $table->string('file_path')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->boolean('visible_to_customer')->default(false)->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_approval_requests')) {
            Schema::create('auto_service_approval_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->string('approval_no')->nullable()->index();
                $table->string('request_type')->default('additional_work');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('status')->default('pending')->index();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->text('customer_note')->nullable();
                $table->string('customer_response_ip', 64)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('auto_service_notification_logs')) {
            Schema::create('auto_service_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->string('channel')->default('sms');
                $table->string('event')->nullable()->index();
                $table->string('recipient')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->default('pending')->index();
                $table->text('response')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_service_bays')) {
            Schema::create('auto_service_bays', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('auto_service_jobs')) {
            Schema::table('auto_service_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('auto_service_jobs', 'service_advisor_id')) $table->unsignedBigInteger('service_advisor_id')->nullable()->after('contact_id')->index();
                if (!Schema::hasColumn('auto_service_jobs', 'bay_id')) $table->unsignedBigInteger('bay_id')->nullable()->after('service_advisor_id')->index();
                if (!Schema::hasColumn('auto_service_jobs', 'estimated_completion_at')) $table->dateTime('estimated_completion_at')->nullable()->after('job_date');
                if (!Schema::hasColumn('auto_service_jobs', 'customer_visible_note')) $table->text('customer_visible_note')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('auto_service_bays');
        Schema::dropIfExists('auto_service_notification_logs');
        Schema::dropIfExists('auto_service_approval_requests');
        Schema::dropIfExists('auto_service_documents');
    }
};
