<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('restaurant_new_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_new_order_id')->nullable()->index();
            $table->string('bill_no')->index();
            $table->dateTime('bill_date')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('cashier_id')->nullable()->index();
            $table->unsignedBigInteger('waiter_id')->nullable()->index();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('service_charge_amount', 22, 4)->default(0);
            $table->decimal('round_off_amount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->decimal('paid_total', 22, 4)->default(0);
            $table->decimal('balance_due', 22, 4)->default(0);
            $table->decimal('change_amount', 22, 4)->default(0);
            $table->string('payment_status', 20)->default('due')->index();
            $table->string('bill_status', 20)->default('open')->index();
            $table->text('notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'location_id', 'bill_no'], 'restaurant_new_bills_unique_no');
        });

        Schema::create('restaurant_new_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_new_bill_id')->index();
            $table->unsignedBigInteger('restaurant_new_order_line_id')->nullable()->index();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            $table->string('item_name');
            $table->string('variant_name')->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('service_charge_amount', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->string('line_status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('restaurant_new_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_new_bill_id')->index();
            $table->dateTime('payment_date')->index();
            $table->string('payment_method', 50)->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->string('reference_no')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('payment_status', 20)->default('posted')->index();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('restaurant_new_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_new_bill_id')->index();
            $table->string('refund_no')->index();
            $table->dateTime('refund_date')->index();
            $table->string('refund_method', 50);
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_new_refunds');
        Schema::dropIfExists('restaurant_new_bill_payments');
        Schema::dropIfExists('restaurant_new_bill_lines');
        Schema::dropIfExists('restaurant_new_bills');
    }
};
