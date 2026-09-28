<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_new_promotions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name'); $table->string('promotion_type')->default('discount'); $table->decimal('discount_value', 22, 4)->default(0);
            $table->date('start_date')->nullable(); $table->date('end_date')->nullable(); $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps();
        });
        Schema::create('restaurant_new_promotion_rules', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('promotion_id'); $table->string('rule_type'); $table->json('rule_payload')->nullable(); $table->timestamps();
        });
        Schema::create('restaurant_new_combo_meals', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('name');
            $table->decimal('combo_price', 22, 4)->default(0); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps();
        });
        Schema::create('restaurant_new_combo_meal_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('combo_meal_id'); $table->unsignedBigInteger('menu_item_id'); $table->decimal('quantity', 22, 4)->default(1); $table->timestamps();
        });
        Schema::create('restaurant_new_happy_hours', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('name'); $table->time('start_time'); $table->time('end_time');
            $table->json('days_of_week')->nullable(); $table->decimal('discount_value', 22, 4)->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('restaurant_new_buffet_packages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('name');
            $table->decimal('adult_price', 22, 4)->default(0); $table->decimal('child_price', 22, 4)->default(0); $table->text('description')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('restaurant_new_banquet_events', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('event_name'); $table->dateTime('event_datetime');
            $table->unsignedInteger('guest_count')->default(0); $table->decimal('estimated_amount', 22, 4)->default(0); $table->string('status')->default('booked'); $table->timestamps();
        });
        Schema::create('restaurant_new_catering_orders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('location_id')->nullable(); $table->string('order_no')->unique(); $table->dateTime('delivery_datetime');
            $table->text('delivery_address')->nullable(); $table->decimal('total_amount', 22, 4)->default(0); $table->string('status')->default('pending'); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_catering_orders'); Schema::dropIfExists('restaurant_new_banquet_events'); Schema::dropIfExists('restaurant_new_buffet_packages'); Schema::dropIfExists('restaurant_new_happy_hours'); Schema::dropIfExists('restaurant_new_combo_meal_items'); Schema::dropIfExists('restaurant_new_combo_meals'); Schema::dropIfExists('restaurant_new_promotion_rules'); Schema::dropIfExists('restaurant_new_promotions');
    }
};
