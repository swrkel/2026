<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('disnew_workflow_validation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('run_no')->unique();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_workflow_validation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validation_run_id')->index();
            $table->string('area')->index();
            $table->string('check_code')->index();
            $table->string('status')->default('pending')->index();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_stock_reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->date('reconciliation_date')->index();
            $table->string('status')->default('draft')->index();
            $table->decimal('warehouse_variance_qty', 22, 4)->default(0);
            $table->decimal('vehicle_variance_qty', 22, 4)->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_stock_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reconciliation_run_id')->index();
            $table->unsignedInteger('product_id')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->decimal('system_qty', 22, 4)->default(0);
            $table->decimal('physical_qty', 22, 4)->default(0);
            $table->decimal('variance_qty', 22, 4)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_visit_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('sales_rep_id')->index();
            $table->unsignedBigInteger('route_id')->nullable()->index();
            $table->date('visit_date')->index();
            $table->string('status')->default('planned')->index();
            $table->timestamps();
        });
        Schema::create('disnew_visit_plan_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_plan_id')->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedInteger('sequence_no')->default(0);
            $table->string('visit_status')->default('pending')->index();
            $table->time('planned_time')->nullable();
            $table->time('visited_time')->nullable();
            $table->decimal('gps_lat', 12, 8)->nullable();
            $table->decimal('gps_lng', 12, 8)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
        Schema::create('disnew_management_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->string('dashboard_role')->index();
            $table->string('widget_code')->index();
            $table->string('title');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['business_id','dashboard_role','widget_code'], 'disnew_dash_widget_unique');
        });
        Schema::create('disnew_performance_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->string('cache_key')->index();
            $table->longText('cache_payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['business_id','cache_key'], 'disnew_perf_cache_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disnew_performance_cache');
        Schema::dropIfExists('disnew_management_dashboard_widgets');
        Schema::dropIfExists('disnew_visit_plan_lines');
        Schema::dropIfExists('disnew_visit_plans');
        Schema::dropIfExists('disnew_stock_reconciliation_lines');
        Schema::dropIfExists('disnew_stock_reconciliation_runs');
        Schema::dropIfExists('disnew_workflow_validation_items');
        Schema::dropIfExists('disnew_workflow_validation_runs');
    }
};
