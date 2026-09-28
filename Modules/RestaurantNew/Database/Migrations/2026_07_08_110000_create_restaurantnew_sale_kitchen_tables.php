<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rn_sale_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('order_no')->unique();
            $table->string('order_type')->default('dine_in');
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->string('status')->default('received');
            $table->string('kitchen_status')->default('received');
            $table->string('payment_status')->default('pending');
            $table->decimal('sub_total', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('service_charge_amount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id','location_id','status']);
        });
        Schema::create('rn_sale_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->string('menu_item_name');
            $table->unsignedBigInteger('kitchen_section_id')->nullable();
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->string('status')->default('received');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['order_id','kitchen_section_id','status']);
        });
        Schema::create('rn_kitchen_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('queue_no');
            $table->string('status')->default('received');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamp('last_printed_at')->nullable();
            $table->timestamps();
            $table->index(['business_id','location_id','status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rn_kitchen_queue');
        Schema::dropIfExists('rn_sale_order_lines');
        Schema::dropIfExists('rn_sale_orders');
    }
};
