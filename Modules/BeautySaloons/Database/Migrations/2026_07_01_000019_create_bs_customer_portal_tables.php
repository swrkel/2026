<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bs_customers')) {
            Schema::table('bs_customers', function (Blueprint $table) {
                if (! Schema::hasColumn('bs_customers', 'portal_enabled')) {
                    $table->boolean('portal_enabled')->default(false)->after('status');
                }
                if (! Schema::hasColumn('bs_customers', 'portal_password')) {
                    $table->string('portal_password')->nullable()->after('portal_enabled');
                }
                if (! Schema::hasColumn('bs_customers', 'portal_last_login_at')) {
                    $table->timestamp('portal_last_login_at')->nullable()->after('portal_password');
                }
            });
        }

        if (! Schema::hasTable('bs_portal_tokens')) {
            Schema::create('bs_portal_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('token_name')->nullable();
                $table->string('device_name')->nullable();
                $table->string('device_id')->nullable();
                $table->string('platform')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('status')->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bs_portal_activity_logs')) {
            Schema::create('bs_portal_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('action')->index();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_portal_activity_logs');
        Schema::dropIfExists('bs_portal_tokens');
    }
};
