<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('identityaccess_login_identities')) {
            Schema::create('identityaccess_login_identities', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('portal_type', 60)->index();
                $table->string('owner_type')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('login_identifier')->unique();
                $table->string('passcode_hash')->nullable();
                $table->string('password_hash')->nullable();
                $table->string('email')->nullable()->index();
                $table->string('mobile')->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamp('locked_until')->nullable();
                $table->unsignedInteger('failed_attempts')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('identityaccess_sessions')) {
            Schema::create('identityaccess_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('login_identity_id')->index();
                $table->string('portal_type', 60)->index();
                $table->string('session_token', 120)->unique();
                $table->string('device_name')->nullable();
                $table->string('browser')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->boolean('trusted_device')->default(false);
                $table->timestamp('logged_in_at')->nullable();
                $table->timestamp('logged_out_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('identityaccess_otps')) {
            Schema::create('identityaccess_otps', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('login_identity_id')->index();
                $table->string('portal_type', 60)->index();
                $table->string('otp_hash');
                $table->string('delivery_email')->nullable();
                $table->string('delivery_mobile')->nullable();
                $table->boolean('email_sent')->default(false);
                $table->boolean('sms_sent')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->string('status', 30)->default('pending')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('identityaccess_security_events')) {
            Schema::create('identityaccess_security_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('login_identity_id')->nullable()->index();
                $table->string('portal_type', 60)->nullable()->index();
                $table->string('event_type', 80)->index();
                $table->string('severity', 30)->default('info')->index();
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('identityaccess_security_events');
        Schema::dropIfExists('identityaccess_otps');
        Schema::dropIfExists('identityaccess_sessions');
        Schema::dropIfExists('identityaccess_login_identities');
    }
};
