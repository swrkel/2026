<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewReservationFloorTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_floor_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->string('floor_type', 60)->default('indoor')->index();
            $table->json('layout_payload')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_floor_tables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('floor_plan_id')->index();
            $table->string('table_no', 60)->index();
            $table->string('table_name')->nullable();
            $table->string('shape', 30)->default('rectangle');
            $table->unsignedInteger('min_guests')->default(1);
            $table->unsignedInteger('capacity')->default(2);
            $table->unsignedInteger('max_guests')->default(2);
            $table->string('status', 40)->default('available')->index();
            $table->unsignedBigInteger('assigned_waiter_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_cashier_id')->nullable()->index();
            $table->string('merge_group', 80)->nullable()->index();
            $table->json('position_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_reservations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('reservation_no', 80)->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('mobile', 40)->nullable()->index();
            $table->string('email')->nullable();
            $table->date('reservation_date')->index();
            $table->time('reservation_time')->index();
            $table->unsignedInteger('guest_count')->default(1);
            $table->unsignedBigInteger('floor_table_id')->nullable()->index();
            $table->string('preferred_area')->nullable();
            $table->boolean('is_vip')->default(false)->index();
            $table->text('special_requests')->nullable();
            $table->text('allergy_notes')->nullable();
            $table->string('status', 40)->default('booked')->index();
            $table->decimal('deposit_amount', 22, 4)->default(0);
            $table->string('deposit_status', 40)->default('not_required')->index();
            $table->string('qr_token', 120)->nullable()->unique();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_waitlists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('queue_no', 80)->index();
            $table->string('customer_name');
            $table->string('mobile', 40)->nullable()->index();
            $table->unsignedInteger('guest_count')->default(1);
            $table->unsignedInteger('estimated_wait_minutes')->default(0);
            $table->string('priority', 30)->default('normal')->index();
            $table->string('status', 40)->default('waiting')->index();
            $table->unsignedBigInteger('assigned_table_id')->nullable()->index();
            $table->timestamp('seated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_table_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('floor_table_id')->index();
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40)->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_table_status_logs');
        Schema::dropIfExists('restaurant_new_waitlists');
        Schema::dropIfExists('restaurant_new_reservations');
        Schema::dropIfExists('restaurant_new_floor_tables');
        Schema::dropIfExists('restaurant_new_floor_plans');
    }
}
