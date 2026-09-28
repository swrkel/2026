<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewOnlineOrderingTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_online_channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('channel_name');
            $table->string('channel_code', 50)->index();
            $table->boolean('allow_delivery')->default(true);
            $table->boolean('allow_pickup')->default(true);
            $table->boolean('allow_dine_in')->default(true);
            $table->boolean('accept_scheduled_orders')->default(true);
            $table->integer('min_preparation_minutes')->default(20);
            $table->decimal('minimum_order_amount', 22, 4)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_online_customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->string('customer_name');
            $table->string('mobile', 50)->index();
            $table->string('email')->nullable()->index();
            $table->text('default_address')->nullable();
            $table->string('city')->nullable();
            $table->string('landmark')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_online_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('online_channel_id')->nullable()->index();
            $table->unsignedBigInteger('online_customer_id')->nullable()->index();
            $table->string('online_order_no', 80)->index();
            $table->string('order_type', 30)->index();
            $table->string('status', 40)->default('received')->index();
            $table->string('payment_status', 40)->default('pending')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('delivery_charge', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->text('customer_note')->nullable();
            $table->text('delivery_address')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_online_order_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('online_order_id')->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->text('item_note')->nullable();
            $table->json('modifiers')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_online_order_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('online_order_id')->index();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_online_order_status_logs');
        Schema::dropIfExists('restaurant_new_online_order_lines');
        Schema::dropIfExists('restaurant_new_online_orders');
        Schema::dropIfExists('restaurant_new_online_customers');
        Schema::dropIfExists('restaurant_new_online_channels');
    }
}
