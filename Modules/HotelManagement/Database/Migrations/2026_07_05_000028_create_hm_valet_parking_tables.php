<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_valet_parking_zones')) {
            Schema::create('hm_valet_parking_zones', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('zone_code', 50);
                $table->string('zone_name', 120);
                $table->integer('capacity')->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'zone_code'], 'hm_valet_zones_business_code_unique');
            });
        }

        if (!Schema::hasTable('hm_valet_parking_tickets')) {
            Schema::create('hm_valet_parking_tickets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('ticket_no', 80);
                $table->date('ticket_date')->nullable()->index();
                $table->unsignedBigInteger('zone_id')->nullable()->index();
                $table->string('room_no', 30)->nullable();
                $table->string('guest_name', 150)->nullable();
                $table->string('mobile', 50)->nullable();
                $table->string('vehicle_no', 80);
                $table->string('vehicle_type', 80)->nullable();
                $table->string('vehicle_colour', 80)->nullable();
                $table->string('key_tag_no', 80)->nullable();
                $table->string('parked_slot', 80)->nullable();
                $table->string('check_in_time', 30)->nullable();
                $table->string('expected_out_time', 30)->nullable();
                $table->string('retrieved_time', 30)->nullable();
                $table->string('driver_name', 150)->nullable();
                $table->decimal('rate', 22, 4)->default(0);
                $table->decimal('net_amount', 22, 4)->default(0);
                $table->decimal('paid_amount', 22, 4)->default(0);
                $table->string('status', 40)->default('parked')->index();
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'ticket_no'], 'hm_valet_tickets_business_no_unique');
            });
        }

        if (!Schema::hasTable('hm_valet_parking_payments')) {
            Schema::create('hm_valet_parking_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('ticket_id')->index();
                $table->date('payment_date')->nullable()->index();
                $table->string('payment_method', 40);
                $table->decimal('paid_amount', 22, 4)->default(0);
                $table->string('payment_reference', 150)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_valet_parking_payments');
        Schema::dropIfExists('hm_valet_parking_tickets');
        Schema::dropIfExists('hm_valet_parking_zones');
    }
};
