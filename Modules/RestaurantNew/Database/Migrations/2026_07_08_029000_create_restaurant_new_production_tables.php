<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewProductionTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_production_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('plan_no', 60)->index();
            $table->date('plan_date')->index();
            $table->string('production_type', 40)->default('central_kitchen')->index();
            $table->string('status', 40)->default('draft')->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_production_plan_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_plan_id')->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('semi_finished_item_id')->nullable()->index();
            $table->decimal('planned_qty', 22, 4)->default(0);
            $table->decimal('produced_qty', 22, 4)->default(0);
            $table->decimal('yield_qty', 22, 4)->default(0);
            $table->decimal('wastage_qty', 22, 4)->default(0);
            $table->decimal('estimated_cost', 22, 4)->default(0);
            $table->decimal('actual_cost', 22, 4)->default(0);
            $table->string('status', 40)->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_semi_finished_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('sku', 80)->nullable()->index();
            $table->string('unit', 30)->nullable();
            $table->decimal('standard_yield_qty', 22, 4)->default(0);
            $table->decimal('standard_cost', 22, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_batch_productions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('production_plan_id')->nullable()->index();
            $table->string('batch_no', 80)->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('status', 40)->default('open')->index();
            $table->decimal('input_cost', 22, 4)->default(0);
            $table->decimal('output_cost', 22, 4)->default(0);
            $table->decimal('wastage_cost', 22, 4)->default(0);
            $table->json('yield_payload')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_branch_distributions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('from_location_id')->index();
            $table->unsignedBigInteger('to_location_id')->index();
            $table->unsignedBigInteger('batch_production_id')->nullable()->index();
            $table->string('distribution_no', 80)->index();
            $table->date('distribution_date')->index();
            $table->string('status', 40)->default('draft')->index();
            $table->decimal('total_qty', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_branch_distributions');
        Schema::dropIfExists('restaurant_new_batch_productions');
        Schema::dropIfExists('restaurant_new_semi_finished_items');
        Schema::dropIfExists('restaurant_new_production_plan_items');
        Schema::dropIfExists('restaurant_new_production_plans');
    }
}
