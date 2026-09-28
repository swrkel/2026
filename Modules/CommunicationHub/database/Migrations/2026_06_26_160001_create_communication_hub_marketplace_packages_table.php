<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('communication_hub_marketplace_packages')) {
            Schema::create('communication_hub_marketplace_packages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('package_code')->unique();
                $table->string('name');
                $table->string('channel', 50)->index();
                $table->string('provider_key', 100)->nullable()->index();
                $table->string('version', 50)->default('1.0.0');
                $table->string('available_version', 50)->nullable();
                $table->string('status', 50)->default('available')->index();
                $table->boolean('is_installed')->default(false)->index();
                $table->boolean('is_enabled')->default(false)->index();
                $table->string('compatibility_status', 50)->default('compatible');
                $table->string('license_status', 50)->default('not_required');
                $table->decimal('cost_per_message', 12, 4)->nullable();
                $table->unsignedInteger('failover_priority')->default(100);
                $table->json('capabilities')->nullable();
                $table->json('configuration_schema')->nullable();
                $table->json('sandbox_results')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('installed_at')->nullable();
                $table->timestamp('enabled_at')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->timestamp('last_tested_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['channel', 'is_enabled']);
                $table->index(['status', 'compatibility_status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_hub_marketplace_packages');
    }
};
