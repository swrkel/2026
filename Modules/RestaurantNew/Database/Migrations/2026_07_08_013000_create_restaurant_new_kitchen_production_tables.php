<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resnew_kitchen_queues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('kitchen_section_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->unsignedBigInteger('kot_id')->nullable()->index();
            $table->string('queue_no', 40)->index();
            $table->enum('priority', ['low','normal','high','urgent'])->default('normal')->index();
            $table->enum('status', ['received','preparing','ready','served','cancelled'])->default('received')->index();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('served_at')->nullable();
            $table->integer('estimated_minutes')->default(15);
            $table->integer('actual_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('resnew_kitchen_timers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('queue_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->string('timer_type', 30)->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('paused_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->integer('total_seconds')->default(0);
            $table->string('status', 20)->default('running')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('resnew_kitchen_route_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('menu_category_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('kitchen_section_id')->index();
            $table->string('order_type', 30)->nullable()->index();
            $table->integer('priority')->default(10);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('resnew_kitchen_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->index();
            $table->unsignedBigInteger('kitchen_section_id')->nullable()->index();
            $table->unsignedBigInteger('queue_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('order_item_id')->nullable()->index();
            $table->unsignedBigInteger('staff_id')->nullable()->index();
            $table->date('metric_date')->index();
            $table->integer('target_minutes')->default(0);
            $table->integer('actual_minutes')->nullable();
            $table->integer('delay_minutes')->default(0);
            $table->string('status', 30)->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resnew_kitchen_performance_logs');
        Schema::dropIfExists('resnew_kitchen_route_rules');
        Schema::dropIfExists('resnew_kitchen_timers');
        Schema::dropIfExists('resnew_kitchen_queues');
    }
};
