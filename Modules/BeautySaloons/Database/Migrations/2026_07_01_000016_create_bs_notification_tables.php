<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bs_notification_templates')) {
            Schema::create('bs_notification_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->string('code')->index();
                $table->string('name');
                $table->string('category')->nullable()->index();
                $table->enum('channel', ['sms', 'email', 'push', 'whatsapp', 'in_app'])->default('sms')->index();
                $table->string('language')->default('en');
                $table->string('subject')->nullable();
                $table->longText('body');
                $table->json('variables')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'code', 'channel', 'language'], 'bs_tpl_unique');
            });
        }

        if (! Schema::hasTable('bs_notification_settings')) {
            Schema::create('bs_notification_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->boolean('sms_enabled')->default(true);
                $table->boolean('email_enabled')->default(true);
                $table->boolean('push_enabled')->default(false);
                $table->boolean('whatsapp_enabled')->default(false);
                $table->unsignedSmallInteger('appointment_reminder_hours')->default(24);
                $table->unsignedSmallInteger('max_retry_count')->default(3);
                $table->string('sms_sender_name')->nullable();
                $table->string('email_from_name')->nullable();
                $table->string('email_from_address')->nullable();
                $table->json('gateway_config')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bs_notification_logs')) {
            Schema::create('bs_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('template_id')->nullable()->index();
                $table->string('related_type')->nullable()->index();
                $table->unsignedBigInteger('related_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('staff_id')->nullable()->index();
                $table->enum('channel', ['sms', 'email', 'push', 'whatsapp', 'in_app'])->default('sms')->index();
                $table->string('recipient')->nullable()->index();
                $table->string('subject')->nullable();
                $table->longText('message')->nullable();
                $table->enum('status', ['pending', 'processing', 'sent', 'failed', 'retry', 'cancelled'])->default('pending')->index();
                $table->unsignedSmallInteger('retry_count')->default(0);
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('sent_at')->nullable();
                $table->text('gateway_response')->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_notification_logs');
        Schema::dropIfExists('bs_notification_settings');
        Schema::dropIfExists('bs_notification_templates');
    }
};
