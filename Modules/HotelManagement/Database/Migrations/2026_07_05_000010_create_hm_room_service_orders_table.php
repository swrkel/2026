<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_room_service_orders')) {
            Schema::create('hm_room_service_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('room_service_no', 50)->index();
                $table->date('order_date')->index();
                $table->time('delivery_time')->nullable();
                $table->unsignedBigInteger('room_id')->index();
                $table->unsignedBigInteger('folio_id')->nullable()->index();
                $table->string('guest_name')->nullable();
                $table->unsignedBigInteger('menu_item_id')->index();
                $table->string('item_name');
                $table->decimal('quantity', 22, 4)->default(1);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('subtotal', 22, 4)->default(0);
                $table->decimal('service_charge', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('grand_total', 22, 4)->default(0);
                $table->string('payment_mode', 40)->default('room')->index();
                $table->string('priority', 30)->default('normal')->index();
                $table->string('status', 30)->default('ordered')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['business_id', 'business_location_id', 'order_date'], 'hm_rs_scope_date_idx');
                $table->index(['business_id', 'room_service_no'], 'hm_rs_scope_no_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_room_service_orders');
    }
};
