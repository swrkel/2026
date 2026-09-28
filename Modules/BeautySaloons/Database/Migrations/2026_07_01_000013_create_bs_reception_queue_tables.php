<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_reception_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->string('queue_no', 50)->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_id')->nullable()->index();
            $table->string('customer_name')->nullable();
            $table->string('mobile', 50)->nullable();
            $table->enum('visit_type', ['walk_in','appointment','package','membership','complaint'])->default('walk_in');
            $table->enum('priority', ['normal','vip','senior','urgent'])->default('normal');
            $table->enum('status', ['waiting','checked_in','in_service','on_hold','completed','cancelled','no_show'])->default('waiting')->index();
            $table->dateTime('arrival_at')->nullable()->index();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('service_start_at')->nullable();
            $table->dateTime('service_end_at')->nullable();
            $table->unsignedBigInteger('assigned_staff_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_resource_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('bs_reception_queue_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('queue_id')->index();
            $table->unsignedBigInteger('service_id')->nullable()->index();
            $table->string('service_name')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->unsignedBigInteger('resource_id')->nullable()->index();
            $table->decimal('estimated_minutes', 10, 2)->default(0);
            $table->decimal('price', 15, 4)->default(0);
            $table->enum('status', ['pending','assigned','started','completed','cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('bs_reception_checkins', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('queue_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('checkin_code', 80)->index();
            $table->dateTime('checkin_at')->nullable()->index();
            $table->dateTime('checkout_at')->nullable();
            $table->decimal('waiting_minutes', 10, 2)->default(0);
            $table->decimal('service_minutes', 10, 2)->default(0);
            $table->enum('status', ['open','closed','cancelled'])->default('open')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_reception_checkins');
        Schema::dropIfExists('bs_reception_queue_services');
        Schema::dropIfExists('bs_reception_queues');
    }
};
