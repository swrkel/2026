<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_banquet_halls')) {
            Schema::create('hm_banquet_halls', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('hall_code', 50)->index();
                $table->string('hall_name');
                $table->integer('capacity')->default(0);
                $table->decimal('base_rate', 22, 4)->default(0);
                $table->string('status', 30)->default('active')->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'business_location_id', 'status'], 'hm_bh_scope_status_idx');
            });
        }
        if (!Schema::hasTable('hm_banquet_events')) {
            Schema::create('hm_banquet_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('event_no', 50)->index();
                $table->date('event_date')->index();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->unsignedBigInteger('hall_id')->index();
                $table->string('hall_name');
                $table->string('event_type', 80)->index();
                $table->string('customer_name');
                $table->string('customer_mobile', 50)->nullable();
                $table->integer('guest_count')->default(0);
                $table->decimal('package_amount', 22, 4)->default(0);
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
                $table->index(['business_id', 'business_location_id', 'event_date'], 'hm_be_scope_date_idx');
                $table->index(['hall_id', 'event_date', 'status'], 'hm_be_hall_date_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_banquet_events');
        Schema::dropIfExists('hm_banquet_halls');
    }
};
