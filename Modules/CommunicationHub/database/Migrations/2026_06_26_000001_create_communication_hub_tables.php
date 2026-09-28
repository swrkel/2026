<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_hub_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('channel', 50)->index(); // sms, email, whatsapp, push, webhook
            $table->string('gateway_code', 100)->nullable();
            $table->json('credentials')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_hub_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 100)->index();
            $table->string('category', 100)->nullable()->index();
            $table->json('channels')->nullable();
            $table->string('subject')->nullable();
            $table->longText('content');
            $table->json('placeholders')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'code']);
        });

        Schema::create('communication_hub_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('module', 100)->nullable()->index();
            $table->string('channel', 50)->index();
            $table->string('recipient')->index();
            $table->string('subject')->nullable();
            $table->longText('message');
            $table->json('payload')->nullable();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('retries')->default(0);
            $table->decimal('cost', 18, 4)->default(0);
            $table->unsignedBigInteger('provider_id')->nullable()->index();
            $table->json('response')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('communication_hub_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('module', 100)->nullable()->index();
            $table->string('purpose', 100)->default('login')->index();
            $table->string('identifier')->index();
            $table->string('otp_hash');
            $table->string('plain_otp_preview')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('communication_hub_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('key')->index();
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_hub_settings');
        Schema::dropIfExists('communication_hub_otps');
        Schema::dropIfExists('communication_hub_messages');
        Schema::dropIfExists('communication_hub_templates');
        Schema::dropIfExists('communication_hub_providers');
    }
};
