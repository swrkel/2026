<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rn_delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('zone_name');
            $table->decimal('base_delivery_charge', 22, 4)->default(0);
            $table->decimal('free_delivery_minimum', 22, 4)->default(0);
            $table->unsignedInteger('estimated_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rn_delivery_riders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('rider_name');
            $table->string('mobile')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_assigned_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rn_customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('landmark')->nullable();
            $table->unsignedBigInteger('delivery_zone_id')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rn_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('customer_address_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_zone_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_rider_id')->nullable()->index();
            $table->decimal('delivery_charge', 22, 4)->default(0);
            $table->decimal('cod_amount', 22, 4)->default(0);
            $table->decimal('card_amount', 22, 4)->default(0);
            $table->string('delivery_status')->default('pending')->index();
            $table->string('payment_collection_status')->default('pending')->index();
            $table->text('special_instructions')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rn_delivery_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_order_id')->index();
            $table->string('status')->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_delivery_status_logs');
        Schema::dropIfExists('rn_delivery_orders');
        Schema::dropIfExists('rn_customer_addresses');
        Schema::dropIfExists('rn_delivery_riders');
        Schema::dropIfExists('rn_delivery_zones');
    }
};
