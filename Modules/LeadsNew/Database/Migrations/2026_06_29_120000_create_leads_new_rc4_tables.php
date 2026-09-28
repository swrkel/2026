<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads_new_workflow_rules')) {
            Schema::create('leads_new_workflow_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('from_status_id')->nullable()->index();
                $table->unsignedBigInteger('to_status_id')->index();
                $table->json('required_fields')->nullable();
                $table->boolean('needs_approval')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_status_history')) {
            Schema::create('leads_new_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('lead_id')->index();
                $table->unsignedBigInteger('from_status_id')->nullable();
                $table->unsignedBigInteger('to_status_id')->nullable();
                $table->unsignedInteger('changed_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_notifications')) {
            Schema::create('leads_new_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_webhooks')) {
            Schema::create('leads_new_webhooks', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->string('event_name');
                $table->string('target_url');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leads_new_webhook_logs')) {
            Schema::create('leads_new_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedBigInteger('webhook_id')->nullable()->index();
                $table->string('event_name');
                $table->string('status')->nullable();
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_new_webhook_logs');
        Schema::dropIfExists('leads_new_webhooks');
        Schema::dropIfExists('leads_new_notifications');
        Schema::dropIfExists('leads_new_status_history');
        Schema::dropIfExists('leads_new_workflow_rules');
    }
};
