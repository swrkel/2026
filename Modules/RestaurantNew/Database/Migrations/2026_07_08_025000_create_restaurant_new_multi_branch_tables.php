<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_new_branch_operation_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->index();
            $table->boolean('is_central_kitchen')->default(false);
            $table->boolean('is_commissary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('operating_days')->nullable();
            $table->time('production_start_time')->nullable();
            $table->time('production_end_time')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_branch_recipe_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_new_menu_item_id')->nullable()->index();
            $table->boolean('allow_local_override')->default(false);
            $table->boolean('approval_required')->default(true);
            $table->date('effective_from')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_branch_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('transfer_no')->index();
            $table->date('transfer_date')->nullable();
            $table->unsignedBigInteger('from_business_location_id')->index();
            $table->unsignedBigInteger('to_business_location_id')->index();
            $table->string('transfer_type')->default('branch_transfer');
            $table->string('status')->default('draft')->index();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('totals')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_branch_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_new_branch_transfer_id')->index();
            $table->unsignedBigInteger('ingredient_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->string('line_type')->default('ingredient');
            $table->decimal('requested_qty', 20, 4)->default(0);
            $table->decimal('approved_qty', 20, 4)->default(0);
            $table->decimal('dispatched_qty', 20, 4)->default(0);
            $table->decimal('received_qty', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->string('uom')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_branch_comparison_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->index();
            $table->date('snapshot_date')->index();
            $table->decimal('sales_total', 20, 4)->default(0);
            $table->decimal('food_cost_total', 20, 4)->default(0);
            $table->decimal('gross_profit_total', 20, 4)->default(0);
            $table->decimal('wastage_total', 20, 4)->default(0);
            $table->json('kpi_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_branch_comparison_snapshots');
        Schema::dropIfExists('restaurant_new_branch_transfer_lines');
        Schema::dropIfExists('restaurant_new_branch_transfers');
        Schema::dropIfExists('restaurant_new_branch_recipe_policies');
        Schema::dropIfExists('restaurant_new_branch_operation_profiles');
    }
};
