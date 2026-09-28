<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('communication_hub_providers')) {
            Schema::create('communication_hub_providers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('name');
                $table->string('channel', 50)->index();
                $table->string('country_code', 10)->nullable();
                $table->string('gateway_code', 100)->nullable();
                $table->string('driver', 100)->nullable();
                $table->json('credentials')->nullable();
                $table->json('provider_config')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedInteger('priority')->default(1);
                $table->boolean('is_active')->default(true);
                $table->string('health_status', 30)->default('unknown');
                $table->decimal('cost_per_message', 12, 4)->default(0);
                $table->unsignedInteger('daily_limit')->nullable();
                $table->unsignedInteger('monthly_limit')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('last_success_at')->nullable();
                $table->timestamp('last_failure_at')->nullable();
                $table->timestamp('last_health_check_at')->nullable();
                $table->string('last_response_code', 100)->nullable();
                $table->text('last_response_message')->nullable();
                $table->unsignedInteger('average_response_ms')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('communication_hub_providers', function (Blueprint $table) {
                foreach ([
                    'business_id' => fn() => $table->unsignedBigInteger('business_id')->nullable()->index(),
                    'country_code' => fn() => $table->string('country_code', 10)->nullable(),
                    'gateway_code' => fn() => $table->string('gateway_code', 100)->nullable(),
                    'driver' => fn() => $table->string('driver', 100)->nullable(),
                    'credentials' => fn() => $table->json('credentials')->nullable(),
                    'provider_config' => fn() => $table->json('provider_config')->nullable(),
                    'meta' => fn() => $table->json('meta')->nullable(),
                    'health_status' => fn() => $table->string('health_status', 30)->default('unknown'),
                    'cost_per_message' => fn() => $table->decimal('cost_per_message', 12, 4)->default(0),
                    'daily_limit' => fn() => $table->unsignedInteger('daily_limit')->nullable(),
                    'monthly_limit' => fn() => $table->unsignedInteger('monthly_limit')->nullable(),
                    'last_used_at' => fn() => $table->timestamp('last_used_at')->nullable(),
                    'last_success_at' => fn() => $table->timestamp('last_success_at')->nullable(),
                    'last_failure_at' => fn() => $table->timestamp('last_failure_at')->nullable(),
                    'last_health_check_at' => fn() => $table->timestamp('last_health_check_at')->nullable(),
                    'last_response_code' => fn() => $table->string('last_response_code', 100)->nullable(),
                    'last_response_message' => fn() => $table->text('last_response_message')->nullable(),
                    'average_response_ms' => fn() => $table->unsignedInteger('average_response_ms')->default(0),
                    'notes' => fn() => $table->text('notes')->nullable(),
                ] as $column => $definition) {
                    if (! Schema::hasColumn('communication_hub_providers', $column)) { $definition(); }
                }
            });
        }

        if (! Schema::hasTable('communication_hub_templates')) {
            Schema::create('communication_hub_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('code', 100)->index();
                $table->string('name');
                $table->string('category', 100)->nullable()->index();
                $table->string('channel', 50)->nullable()->index();
                $table->json('channels')->nullable();
                $table->string('subject')->nullable();
                $table->longText('body')->nullable();
                $table->longText('content')->nullable();
                $table->json('placeholders')->nullable();
                $table->json('meta')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('communication_hub_templates', function (Blueprint $table) {
                foreach ([
                    'business_id' => fn() => $table->unsignedBigInteger('business_id')->nullable()->index(),
                    'channel' => fn() => $table->string('channel', 50)->nullable()->index(),
                    'channels' => fn() => $table->json('channels')->nullable(),
                    'body' => fn() => $table->longText('body')->nullable(),
                    'content' => fn() => $table->longText('content')->nullable(),
                    'meta' => fn() => $table->json('meta')->nullable(),
                ] as $column => $definition) {
                    if (! Schema::hasColumn('communication_hub_templates', $column)) { $definition(); }
                }
            });
        }

        if (! Schema::hasTable('communication_hub_messages')) {
            Schema::create('communication_hub_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('module', 100)->nullable()->index();
                $table->string('source_module')->nullable()->index();
                $table->string('source_reference')->nullable()->index();
                $table->string('channel', 50)->index();
                $table->string('recipient')->index();
                $table->string('subject')->nullable();
                $table->longText('body')->nullable();
                $table->longText('message')->nullable();
                $table->json('payload')->nullable();
                $table->string('priority', 20)->default('normal')->index();
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedInteger('attempts')->default(0);
                $table->unsignedInteger('retries')->default(0);
                $table->decimal('cost', 18, 4)->default(0);
                $table->decimal('estimated_cost', 16, 4)->default(0);
                $table->decimal('actual_cost', 16, 4)->default(0);
                $table->string('currency', 10)->default('LKR');
                $table->string('wallet_charge_status', 30)->nullable()->index();
                $table->string('wallet_transaction_reference')->nullable()->index();
                $table->json('wallet_response')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable()->index();
                $table->string('provider_reference')->nullable();
                $table->string('response_code')->nullable();
                $table->text('response_message')->nullable();
                $table->json('response')->nullable();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('attempted_at')->nullable();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('delivered_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        } else {
            Schema::table('communication_hub_messages', function (Blueprint $table) {
                foreach ([
                    'business_id' => fn() => $table->unsignedBigInteger('business_id')->nullable()->index(),
                    'business_location_id' => fn() => $table->unsignedBigInteger('business_location_id')->nullable()->index(),
                    'module' => fn() => $table->string('module', 100)->nullable()->index(),
                    'source_module' => fn() => $table->string('source_module')->nullable()->index(),
                    'source_reference' => fn() => $table->string('source_reference')->nullable()->index(),
                    'body' => fn() => $table->longText('body')->nullable(),
                    'message' => fn() => $table->longText('message')->nullable(),
                    'attempts' => fn() => $table->unsignedInteger('attempts')->default(0),
                    'retries' => fn() => $table->unsignedInteger('retries')->default(0),
                    'estimated_cost' => fn() => $table->decimal('estimated_cost', 16, 4)->default(0),
                    'actual_cost' => fn() => $table->decimal('actual_cost', 16, 4)->default(0),
                    'currency' => fn() => $table->string('currency', 10)->default('LKR'),
                    'wallet_charge_status' => fn() => $table->string('wallet_charge_status', 30)->nullable()->index(),
                    'wallet_transaction_reference' => fn() => $table->string('wallet_transaction_reference')->nullable()->index(),
                    'wallet_response' => fn() => $table->json('wallet_response')->nullable(),
                    'provider_reference' => fn() => $table->string('provider_reference')->nullable(),
                    'response_code' => fn() => $table->string('response_code')->nullable(),
                    'response_message' => fn() => $table->text('response_message')->nullable(),
                    'response' => fn() => $table->json('response')->nullable(),
                    'attempted_at' => fn() => $table->timestamp('attempted_at')->nullable(),
                    'delivered_at' => fn() => $table->timestamp('delivered_at')->nullable(),
                    'created_by' => fn() => $table->unsignedBigInteger('created_by')->nullable()->index(),
                ] as $column => $definition) {
                    if (! Schema::hasColumn('communication_hub_messages', $column)) { $definition(); }
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive for production. Do not drop CommunicationHub data automatically.
    }
};
