<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_hub_api_clients')) {
            Schema::create('communication_hub_api_clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('module_name')->index();
                $table->string('client_name');
                $table->string('token_hash', 64)->unique();
                $table->json('allowed_channels')->nullable();
                $table->json('allowed_ips')->nullable();
                $table->json('permissions')->nullable();
                $table->json('rate_limits')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('communication_hub_api_request_logs')) {
            Schema::create('communication_hub_api_request_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('api_client_id')->nullable()->index();
                $table->string('module_name')->nullable()->index();
                $table->string('endpoint')->index();
                $table->string('method', 20)->nullable();
                $table->string('ip_address')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->unsignedSmallInteger('status_code')->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('communication_hub_api_clients')) {
            $exists = DB::table('communication_hub_api_clients')->where('module_name', 'internal_test')->exists();
            if (! $exists) {
                DB::table('communication_hub_api_clients')->insert([
                    'module_name' => 'internal_test',
                    'client_name' => 'Internal Test Client',
                    'token_hash' => hash('sha256', 'communicationhub-test-token'),
                    'allowed_channels' => json_encode(['sms', 'email', 'whatsapp', 'push']),
                    'permissions' => json_encode(['send', 'otp', 'status', 'estimate']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_hub_api_request_logs');
        Schema::dropIfExists('communication_hub_api_clients');
    }
};
