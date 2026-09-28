<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bs_scheduler_slots')) {
            Schema::create('bs_scheduler_slots', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('staff_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->date('slot_date')->index();
                $table->time('start_time');
                $table->time('end_time');
                $table->string('status')->default('available')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bs_appointment_resources')) {
            Schema::create('bs_appointment_resources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('appointment_id')->index();
                $table->unsignedBigInteger('staff_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->unsignedBigInteger('service_id')->nullable()->index();
                $table->dateTime('starts_at')->nullable()->index();
                $table->dateTime('ends_at')->nullable();
                $table->string('status')->default('booked')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bs_waitlists')) {
            Schema::create('bs_waitlists', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('service_id')->nullable()->index();
                $table->unsignedBigInteger('preferred_staff_id')->nullable()->index();
                $table->date('preferred_date')->nullable()->index();
                $table->time('preferred_start_time')->nullable();
                $table->time('preferred_end_time')->nullable();
                $table->string('priority')->default('normal')->index();
                $table->string('status')->default('waiting')->index();
                $table->dateTime('notified_at')->nullable();
                $table->dateTime('converted_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bs_recurring_appointments')) {
            Schema::create('bs_recurring_appointments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('service_id')->nullable()->index();
                $table->unsignedBigInteger('staff_id')->nullable()->index();
                $table->string('frequency')->default('weekly');
                $table->json('week_days')->nullable();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->date('next_run_date')->nullable()->index();
                $table->time('start_time')->nullable();
                $table->integer('duration_minutes')->default(60);
                $table->string('status')->default('active')->index();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bs_recurring_appointments');
        Schema::dropIfExists('bs_waitlists');
        Schema::dropIfExists('bs_appointment_resources');
        Schema::dropIfExists('bs_scheduler_slots');
    }
};
