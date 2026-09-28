<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewAnalyticsTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_analytics_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('snapshot_date')->index();
            $table->decimal('gross_sales', 22, 4)->default(0);
            $table->decimal('net_sales', 22, 4)->default(0);
            $table->decimal('discount_total', 22, 4)->default(0);
            $table->decimal('tax_total', 22, 4)->default(0);
            $table->decimal('service_charge_total', 22, 4)->default(0);
            $table->decimal('food_cost_total', 22, 4)->default(0);
            $table->decimal('gross_profit', 22, 4)->default(0);
            $table->decimal('food_cost_percentage', 12, 4)->default(0);
            $table->integer('order_count')->default(0);
            $table->integer('guest_count')->default(0);
            $table->integer('table_turns')->default(0);
            $table->json('kpi_payload')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'snapshot_date'], 'rn_analytics_snapshot_unique');
        });

        Schema::create('restaurant_new_menu_profitability', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->index();
            $table->date('period_date')->index();
            $table->decimal('qty_sold', 22, 4)->default(0);
            $table->decimal('sales_total', 22, 4)->default(0);
            $table->decimal('recipe_cost_total', 22, 4)->default(0);
            $table->decimal('gross_margin', 22, 4)->default(0);
            $table->decimal('margin_percentage', 12, 4)->default(0);
            $table->integer('void_count')->default(0);
            $table->integer('complaint_count')->default(0);
            $table->string('performance_band', 40)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_hourly_sales_trends', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('trend_date')->index();
            $table->unsignedTinyInteger('hour_no')->index();
            $table->decimal('net_sales', 22, 4)->default(0);
            $table->integer('order_count')->default(0);
            $table->integer('guest_count')->default(0);
            $table->decimal('average_bill_value', 22, 4)->default(0);
            $table->json('order_type_breakdown')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_table_utilization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->date('utilization_date')->index();
            $table->integer('turn_count')->default(0);
            $table->integer('guest_count')->default(0);
            $table->integer('occupied_minutes')->default(0);
            $table->decimal('sales_total', 22, 4)->default(0);
            $table->decimal('revenue_per_seat', 22, 4)->default(0);
            $table->json('hourly_usage')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_forecast_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('forecast_date')->index();
            $table->string('forecast_type', 60)->index();
            $table->decimal('forecast_sales', 22, 4)->default(0);
            $table->integer('forecast_orders')->default(0);
            $table->json('forecast_payload')->nullable();
            $table->decimal('confidence_score', 8, 4)->default(0);
            $table->string('status', 40)->default('generated')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_forecast_runs');
        Schema::dropIfExists('restaurant_new_table_utilization');
        Schema::dropIfExists('restaurant_new_hourly_sales_trends');
        Schema::dropIfExists('restaurant_new_menu_profitability');
        Schema::dropIfExists('restaurant_new_analytics_snapshots');
    }
}
