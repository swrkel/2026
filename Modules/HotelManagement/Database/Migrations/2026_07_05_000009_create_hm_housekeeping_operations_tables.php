<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hm_housekeeping_schedules')) {
            Schema::create('hm_housekeeping_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->index();
                $table->unsignedBigInteger('attendant_id')->nullable()->index();
                $table->string('schedule_no', 50)->nullable()->index();
                $table->string('cleaning_type', 50)->default('departure');
                $table->string('priority', 30)->default('normal');
                $table->string('status', 30)->default('scheduled')->index();
                $table->date('cleaning_date')->nullable()->index();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hm_lost_found_items')) {
            Schema::create('hm_lost_found_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->unsignedBigInteger('guest_id')->nullable()->index();
                $table->string('item_no', 50)->nullable()->index();
                $table->string('item_name', 191); 
                $table->string('category', 80)->nullable();
                $table->date('found_date')->nullable()->index();
                $table->string('found_by', 191)->nullable();
                $table->string('storage_location', 191)->nullable();
                $table->string('status', 30)->default('stored')->index();
                $table->date('claimed_date')->nullable();
                $table->string('claimed_by', 191)->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hm_linen_movements')) {
            Schema::create('hm_linen_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->string('movement_no', 50)->nullable()->index();
                $table->string('linen_item', 191);
                $table->decimal('quantity', 18, 4)->default(0);
                $table->string('movement_type', 50)->default('issue')->index();
                $table->string('from_location', 191)->nullable();
                $table->string('to_location', 191)->nullable();
                $table->date('movement_date')->nullable()->index();
                $table->string('status', 30)->default('posted')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_linen_movements');
        Schema::dropIfExists('hm_lost_found_items');
        Schema::dropIfExists('hm_housekeeping_schedules');
    }
};
