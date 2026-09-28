<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hm_pos_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 50)->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_pos_menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('item_code', 50)->nullable()->index();
            $table->string('name');
            $table->decimal('price', 22, 4)->default(0);
            $table->string('unit', 30)->default('unit');
            $table->string('status', 30)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_pos_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('folio_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->string('order_no', 50)->nullable()->index();
            $table->date('order_date')->nullable()->index();
            $table->string('guest_name')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('service_charge', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->string('payment_mode', 40)->default('cash');
            $table->string('status', 30)->default('open')->index();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hm_pos_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->unsignedBigInteger('pos_order_id')->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->string('description');
            $table->decimal('quantity', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hm_pos_order_lines');
        Schema::dropIfExists('hm_pos_orders');
        Schema::dropIfExists('hm_pos_menu_items');
        Schema::dropIfExists('hm_pos_categories');
    }
};
