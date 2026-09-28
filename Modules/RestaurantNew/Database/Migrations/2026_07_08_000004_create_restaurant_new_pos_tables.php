<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_new_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('dining_area_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_table_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->nullable()->index();
            $table->string('order_no', 50)->index();
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->string('order_status', 50)->default('open')->index();
            $table->string('kot_status', 50)->default('pending')->index();
            $table->string('payment_status', 50)->default('due')->index();
            $table->unsignedInteger('guest_count')->default(1);
            $table->unsignedInteger('waiter_id')->nullable()->index();
            $table->unsignedInteger('cashier_id')->nullable()->index();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('service_charge_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('delivery_charge', 22, 4)->default(0);
            $table->decimal('round_off', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->decimal('paid_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->text('order_note')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'order_no']);
        });

        Schema::create('restaurant_new_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->unsignedBigInteger('kitchen_section_id')->nullable()->index();
            $table->string('item_name');
            $table->string('variant_name')->nullable();
            $table->decimal('qty', 22, 4)->default(1);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->string('kot_status', 50)->default('pending')->index();
            $table->boolean('is_printed_to_kitchen')->default(false);
            $table->text('line_note')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('restaurant_new_order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('payment_method', 50)->index();
            $table->unsignedInteger('payment_account_id')->nullable()->index();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('reference_no')->nullable();
            $table->timestamp('paid_on')->nullable();
            $table->text('payment_note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_order_payments');
        Schema::dropIfExists('restaurant_new_order_lines');
        Schema::dropIfExists('restaurant_new_orders');
    }
};
