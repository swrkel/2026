<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewAdvancedPosTables extends Migration
{
    public function up()
    {
        Schema::table('rn_sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('rn_sale_orders', 'guest_count')) { $table->unsignedInteger('guest_count')->nullable()->after('table_id'); }
            if (!Schema::hasColumn('rn_sale_orders', 'seat_label')) { $table->string('seat_label')->nullable()->after('guest_count'); }
            if (!Schema::hasColumn('rn_sale_orders', 'is_split_bill')) { $table->boolean('is_split_bill')->default(false)->after('payment_status'); }
            if (!Schema::hasColumn('rn_sale_orders', 'paid_amount')) { $table->decimal('paid_amount', 22, 4)->default(0)->after('grand_total'); }
            if (!Schema::hasColumn('rn_sale_orders', 'merged_into_order_id')) { $table->unsignedBigInteger('merged_into_order_id')->nullable()->after('id'); }
        });

        Schema::create('rn_table_operations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('operation_type', 40);
            $table->unsignedBigInteger('from_table_id')->nullable();
            $table->unsignedBigInteger('to_table_id')->nullable();
            $table->unsignedBigInteger('from_waiter_id')->nullable();
            $table->unsignedBigInteger('to_waiter_id')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id']);
            $table->index(['order_id', 'operation_type']);
        });

        Schema::create('rn_held_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->string('hold_no')->unique();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->string('status', 20)->default('held');
            $table->unsignedBigInteger('held_by')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->unsignedBigInteger('resumed_by')->nullable();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id', 'status']);
        });

        Schema::create('rn_split_bills', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->string('split_no');
            $table->string('guest_name')->nullable();
            $table->string('seat_no')->nullable();
            $table->decimal('sub_total', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('service_charge_amount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->string('payment_status', 20)->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id']);
            $table->index(['order_id', 'payment_status']);
        });

        Schema::create('rn_split_bill_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('split_bill_id');
            $table->unsignedBigInteger('order_line_id')->nullable();
            $table->string('menu_item_name');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
            $table->index(['split_bill_id']);
        });

        Schema::create('rn_multi_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('split_bill_id')->nullable();
            $table->string('payment_method', 30);
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('reference_no')->nullable();
            $table->string('card_type')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id']);
            $table->index(['order_id', 'split_bill_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rn_multi_payments');
        Schema::dropIfExists('rn_split_bill_lines');
        Schema::dropIfExists('rn_split_bills');
        Schema::dropIfExists('rn_held_orders');
        Schema::dropIfExists('rn_table_operations');
    }
}
