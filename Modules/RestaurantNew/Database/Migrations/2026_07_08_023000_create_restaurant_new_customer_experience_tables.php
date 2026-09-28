<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewCustomerExperienceTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_table_service_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('request_no', 80)->index();
            $table->string('request_type', 50)->index(); // waiter_call, bill_request, water, cutlery, assistance
            $table->string('status', 40)->default('open')->index();
            $table->text('customer_note')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('assigned_staff_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_customer_order_tracking', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('tracking_token', 120)->unique();
            $table->string('customer_mobile', 50)->nullable()->index();
            $table->string('current_status', 50)->default('received')->index();
            $table->timestamp('last_status_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('public_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_customer_experience_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->string('event_type', 80)->index();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_customer_experience_logs');
        Schema::dropIfExists('restaurant_new_customer_order_tracking');
        Schema::dropIfExists('restaurant_new_table_service_requests');
    }
}
