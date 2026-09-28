<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('communication_hub_providers')) {
            return;
        }

        Schema::table('communication_hub_providers', function (Blueprint $table) {
            if (! Schema::hasColumn('communication_hub_providers', 'country_code')) {
                $table->string('country_code', 10)->nullable()->after('channel');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'health_status')) {
                $table->string('health_status', 30)->default('unknown')->after('is_active');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'cost_per_message')) {
                $table->decimal('cost_per_message', 12, 4)->default(0)->after('health_status');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'daily_limit')) {
                $table->unsignedInteger('daily_limit')->nullable()->after('cost_per_message');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'monthly_limit')) {
                $table->unsignedInteger('monthly_limit')->nullable()->after('daily_limit');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable()->after('monthly_limit');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_success_at')) {
                $table->timestamp('last_success_at')->nullable()->after('last_used_at');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_failure_at')) {
                $table->timestamp('last_failure_at')->nullable()->after('last_success_at');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_health_check_at')) {
                $table->timestamp('last_health_check_at')->nullable()->after('last_failure_at');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_response_code')) {
                $table->string('last_response_code', 100)->nullable()->after('last_health_check_at');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'last_response_message')) {
                $table->text('last_response_message')->nullable()->after('last_response_code');
            }
            if (! Schema::hasColumn('communication_hub_providers', 'average_response_ms')) {
                $table->unsignedInteger('average_response_ms')->default(0)->after('last_response_message');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive rollback for production safety.
    }
};
