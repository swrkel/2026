<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hm_housekeeping_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->string('task_no')->nullable()->index();
            $table->string('task_type')->default('cleaning');
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending')->index();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_room_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->index();
            $table->string('old_status')->nullable();
            $table->string('new_status')->index();
            $table->unsignedBigInteger('changed_by')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('hm_linen_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('linen_type');
            $table->decimal('quantity', 18, 4)->default(0);
            $table->string('movement_type')->index();
            $table->date('movement_date')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_linen_movements');
        Schema::dropIfExists('hm_room_status_logs');
        Schema::dropIfExists('hm_housekeeping_tasks');
    }
};
