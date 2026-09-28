<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewCommandCenterTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_command_center_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('snapshot_date')->index();
            $table->decimal('gross_sales', 22, 4)->default(0);
            $table->decimal('net_sales', 22, 4)->default(0);
            $table->integer('active_tables')->default(0);
            $table->integer('available_tables')->default(0);
            $table->integer('running_orders')->default(0);
            $table->integer('waiting_orders')->default(0);
            $table->integer('ready_orders')->default(0);
            $table->integer('delivery_orders')->default(0);
            $table->integer('takeaway_orders')->default(0);
            $table->integer('delayed_kots')->default(0);
            $table->integer('low_stock_alerts')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_dashboard_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('alert_type', 50)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('source_screen', 50)->nullable()->index();
            $table->boolean('is_read')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_kpi_daily_summaries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->date('summary_date')->index();
            $table->decimal('sales_total', 22, 4)->default(0);
            $table->decimal('payment_total', 22, 4)->default(0);
            $table->decimal('food_cost_total', 22, 4)->default(0);
            $table->decimal('gross_profit', 22, 4)->default(0);
            $table->decimal('food_cost_percent', 8, 4)->default(0);
            $table->integer('bills_count')->default(0);
            $table->integer('kot_count')->default(0);
            $table->integer('void_count')->default(0);
            $table->integer('refund_count')->default(0);
            $table->integer('feedback_count')->default(0);
            $table->decimal('average_rating', 8, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_kpi_daily_summaries');
        Schema::dropIfExists('restaurant_new_dashboard_alerts');
        Schema::dropIfExists('restaurant_new_command_center_snapshots');
    }
}
