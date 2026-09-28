<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disnew_profitability_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('from_date')->index();
            $table->date('to_date')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('gross_sales', 22, 4)->default(0);
            $table->decimal('returns_amount', 22, 4)->default(0);
            $table->decimal('delivery_cost', 22, 4)->default(0);
            $table->decimal('vehicle_cost', 22, 4)->default(0);
            $table->decimal('commission_cost', 22, 4)->default(0);
            $table->decimal('net_profit', 22, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('disnew_profitability_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id')->index();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('scope_type', 40)->index();
            $table->unsignedBigInteger('scope_id')->nullable()->index();
            $table->string('scope_name')->nullable();
            $table->decimal('sales_amount', 22, 4)->default(0);
            $table->decimal('cost_amount', 22, 4)->default(0);
            $table->decimal('return_amount', 22, 4)->default(0);
            $table->decimal('collection_amount', 22, 4)->default(0);
            $table->decimal('profit_amount', 22, 4)->default(0);
            $table->decimal('profit_percent', 12, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('disnew_collection_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('sales_rep_id')->nullable()->index();
            $table->date('collection_date')->index();
            $table->decimal('expected_amount', 22, 4)->default(0);
            $table->decimal('collected_amount', 22, 4)->default(0);
            $table->decimal('short_excess_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('open')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disnew_reconciliation_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('exception_type', 60)->index();
            $table->string('reference_type', 80)->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->decimal('expected_qty', 22, 4)->default(0);
            $table->decimal('actual_qty', 22, 4)->default(0);
            $table->decimal('variance_qty', 22, 4)->default(0);
            $table->decimal('variance_amount', 22, 4)->default(0);
            $table->string('severity', 20)->default('normal')->index();
            $table->string('status', 30)->default('open')->index();
            $table->text('resolution_note')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('disnew_deployment_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('check_group', 80)->index();
            $table->string('check_key', 120)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_deployment_verifications');
        Schema::dropIfExists('disnew_reconciliation_exceptions');
        Schema::dropIfExists('disnew_collection_controls');
        Schema::dropIfExists('disnew_profitability_lines');
        Schema::dropIfExists('disnew_profitability_runs');
    }
};
