<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('myhealth_notification_templates')) {
            Schema::create('myhealth_notification_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 100)->unique();
                $table->string('name');
                $table->string('channel', 30)->default('sms');
                $table->string('subject')->nullable();
                $table->text('body');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('myhealth_notifications')) {
            Schema::create('myhealth_notifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('member_code', 30)->nullable()->index();
                $table->string('channel', 30)->default('sms')->index();
                $table->string('purpose', 100)->nullable()->index();
                $table->string('recipient')->nullable();
                $table->string('subject')->nullable();
                $table->text('message');
                $table->string('status', 30)->default('queued')->index();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('myhealth_notifications');
        Schema::dropIfExists('myhealth_notification_templates');
    }
};
