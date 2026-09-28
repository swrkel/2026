<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hm_sales_channels')) {
            Schema::create('hm_sales_channels', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('channel_name',160);
                $table->string('channel_code',60)->nullable();
                $table->string('channel_type',60)->default('ota');
                $table->string('contact_email',160)->nullable();
                $table->decimal('commission_percent',8,2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','channel_name'],'hm_sales_channels_business_name_unique');
            });
        }
        if (!Schema::hasTable('hm_channel_rate_maps')) {
            Schema::create('hm_channel_rate_maps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('channel_id')->index();
                $table->unsignedBigInteger('room_type_id')->nullable()->index();
                $table->unsignedBigInteger('rate_plan_id')->nullable()->index();
                $table->string('external_room_code',100)->nullable();
                $table->string('external_rate_code',100)->nullable();
                $table->decimal('sell_rate',22,4)->default(0);
                $table->string('currency',10)->default('LKR');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','channel_id','room_type_id','rate_plan_id'],'hm_channel_rate_maps_unique');
            });
        }
        if (!Schema::hasTable('hm_channel_availability')) {
            Schema::create('hm_channel_availability', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('channel_id')->index();
                $table->unsignedBigInteger('room_type_id')->nullable()->index();
                $table->date('available_date')->index();
                $table->integer('available_rooms')->default(0);
                $table->boolean('stop_sell')->default(false);
                $table->integer('min_stay')->default(0);
                $table->integer('max_stay')->default(0);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','channel_id','room_type_id','available_date'],'hm_channel_availability_unique');
            });
        }
        if (!Schema::hasTable('hm_channel_bookings')) {
            Schema::create('hm_channel_bookings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('channel_id')->index();
                $table->string('external_booking_ref',120);
                $table->string('guest_name',160);
                $table->string('guest_mobile',50)->nullable();
                $table->string('guest_email',160)->nullable();
                $table->date('arrival_date')->index();
                $table->date('departure_date')->index();
                $table->integer('rooms')->default(1);
                $table->integer('adults')->default(0);
                $table->integer('children')->default(0);
                $table->decimal('gross_amount',22,4)->default(0);
                $table->decimal('commission_amount',22,4)->default(0);
                $table->decimal('net_amount',22,4)->default(0);
                $table->string('status',40)->default('new')->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id','channel_id','external_booking_ref'],'hm_channel_bookings_ref_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_channel_bookings');
        Schema::dropIfExists('hm_channel_availability');
        Schema::dropIfExists('hm_channel_rate_maps');
        Schema::dropIfExists('hm_sales_channels');
    }
};
