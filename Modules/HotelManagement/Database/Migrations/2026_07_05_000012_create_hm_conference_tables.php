<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_conference_rooms')) {
            Schema::create('hm_conference_rooms', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('room_code', 50)->nullable()->index();
                $table->string('room_name');
                $table->integer('capacity')->default(0);
                $table->decimal('hourly_rate', 22, 4)->default(0);
                $table->decimal('half_day_rate', 22, 4)->default(0);
                $table->decimal('full_day_rate', 22, 4)->default(0);
                $table->string('setup_style', 80)->nullable();
                $table->text('equipment')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'business_location_id', 'status'], 'hm_conf_rooms_scope_status_idx');
            });
        }

        if (!Schema::hasTable('hm_conference_bookings')) {
            Schema::create('hm_conference_bookings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('booking_no', 50)->nullable()->index();
                $table->date('booking_date')->index();
                $table->time('start_time');
                $table->time('end_time');
                $table->unsignedBigInteger('conference_room_id')->index();
                $table->string('room_name')->nullable();
                $table->string('meeting_title');
                $table->string('customer_name');
                $table->string('customer_mobile', 50)->nullable();
                $table->string('customer_email')->nullable();
                $table->integer('attendees')->default(0);
                $table->string('setup_style', 80)->nullable();
                $table->text('equipment_required')->nullable();
                $table->boolean('catering_required')->default(false);
                $table->text('catering_note')->nullable();
                $table->decimal('rental_amount', 22, 4)->default(0);
                $table->decimal('catering_amount', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('discount_amount', 22, 4)->default(0);
                $table->decimal('advance_amount', 22, 4)->default(0);
                $table->decimal('total_amount', 22, 4)->default(0);
                $table->decimal('balance_amount', 22, 4)->default(0);
                $table->string('status', 30)->default('reserved')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'business_location_id', 'booking_date'], 'hm_conf_booking_scope_date_idx');
                $table->index(['conference_room_id', 'booking_date', 'status'], 'hm_conf_booking_clash_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_conference_bookings');
        Schema::dropIfExists('hm_conference_rooms');
    }
};
