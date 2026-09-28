<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewAiOperationsTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_ai_recommendations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('recommendation_type', 80)->index();
            $table->string('priority', 30)->default('normal')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('source_payload')->nullable();
            $table->json('action_payload')->nullable();
            $table->string('status', 40)->default('open')->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('resolved_by')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_ai_forecasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('forecast_date')->index();
            $table->string('forecast_area', 80)->index();
            $table->decimal('predicted_sales', 22, 4)->default(0);
            $table->integer('predicted_orders')->default(0);
            $table->integer('predicted_guests')->default(0);
            $table->json('forecast_payload')->nullable();
            $table->decimal('confidence_score', 8, 4)->default(0);
            $table->string('method', 80)->default('rule_based_foundation');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_ai_inventory_signals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->string('signal_type', 60)->index();
            $table->decimal('current_qty', 22, 4)->default(0);
            $table->decimal('predicted_consumption_qty', 22, 4)->default(0);
            $table->decimal('recommended_reorder_qty', 22, 4)->default(0);
            $table->date('expected_shortage_date')->nullable()->index();
            $table->string('priority', 30)->default('normal')->index();
            $table->json('calculation_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_ai_anomaly_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('anomaly_area', 80)->index();
            $table->string('severity', 30)->default('info')->index();
            $table->string('title');
            $table->text('details')->nullable();
            $table->json('metric_payload')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_ai_anomaly_logs');
        Schema::dropIfExists('restaurant_new_ai_inventory_signals');
        Schema::dropIfExists('restaurant_new_ai_forecasts');
        Schema::dropIfExists('restaurant_new_ai_recommendations');
    }
}
