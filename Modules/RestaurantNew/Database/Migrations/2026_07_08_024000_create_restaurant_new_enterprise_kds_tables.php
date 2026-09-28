<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewEnterpriseKdsTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_kds_screens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('screen_name', 120);
            $table->string('screen_code', 80)->index();
            $table->string('kitchen_section', 80)->nullable()->index();
            $table->json('visible_order_types')->nullable();
            $table->json('status_filter')->nullable();
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_kds_queue_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->unsignedBigInteger('kot_id')->nullable()->index();
            $table->string('order_no', 80)->nullable()->index();
            $table->string('item_name', 191);
            $table->decimal('quantity', 18, 4)->default(1);
            $table->string('order_type', 40)->default('dine_in')->index();
            $table->string('kitchen_section', 80)->nullable()->index();
            $table->string('priority', 40)->default('normal')->index();
            $table->string('current_status', 50)->default('received')->index();
            $table->integer('expected_prep_minutes')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->unsignedBigInteger('assigned_chef_id')->nullable()->index();
            $table->text('kitchen_note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_kds_status_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('queue_item_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->timestamp('changed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_kds_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('queue_item_id')->nullable()->index();
            $table->string('notification_type', 80)->index();
            $table->string('title', 191);
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->unsignedBigInteger('target_user_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_kds_notifications');
        Schema::dropIfExists('restaurant_new_kds_status_logs');
        Schema::dropIfExists('restaurant_new_kds_queue_items');
        Schema::dropIfExists('restaurant_new_kds_screens');
    }
}
