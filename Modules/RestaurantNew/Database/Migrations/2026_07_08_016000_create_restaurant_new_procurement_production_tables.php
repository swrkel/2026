<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantNewProcurementProductionTables extends Migration
{
    public function up()
    {
        Schema::create('restaurant_new_supplier_quotations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('quotation_no', 50)->index();
            $table->string('supplier_name');
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_supplier_quotation_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('quotation_id')->index();
            $table->unsignedBigInteger('ingredient_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_new_purchase_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('quotation_id')->nullable()->index();
            $table->string('po_no', 50)->index();
            $table->string('supplier_name');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_purchase_order_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('ingredient_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('ordered_qty', 22, 4)->default(0);
            $table->decimal('received_qty', 22, 4)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('restaurant_new_goods_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->string('grn_no', 50)->index();
            $table->date('received_date');
            $table->string('supplier_name')->nullable();
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('status', 30)->default('received')->index();
            $table->unsignedBigInteger('received_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_goods_receipt_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('goods_receipt_id')->index();
            $table->unsignedBigInteger('ingredient_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('received_qty', 22, 4)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('line_total', 22, 4)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('batch_no')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_new_production_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->string('batch_no', 50)->index();
            $table->string('production_type', 50)->default('kitchen');
            $table->string('item_name');
            $table->decimal('planned_qty', 22, 4)->default(0);
            $table->decimal('produced_qty', 22, 4)->default(0);
            $table->decimal('wastage_qty', 22, 4)->default(0);
            $table->decimal('total_cost', 22, 4)->default(0);
            $table->string('status', 30)->default('planned')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_commissary_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('from_location_id')->nullable()->index();
            $table->unsignedBigInteger('to_location_id')->nullable()->index();
            $table->string('transfer_no', 50)->index();
            $table->date('transfer_date');
            $table->string('status', 30)->default('draft')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('restaurant_new_commissary_transfer_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transfer_id')->index();
            $table->unsignedBigInteger('ingredient_id')->nullable()->index();
            $table->string('item_name');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurant_new_commissary_transfer_lines');
        Schema::dropIfExists('restaurant_new_commissary_transfers');
        Schema::dropIfExists('restaurant_new_production_batches');
        Schema::dropIfExists('restaurant_new_goods_receipt_lines');
        Schema::dropIfExists('restaurant_new_goods_receipts');
        Schema::dropIfExists('restaurant_new_purchase_order_lines');
        Schema::dropIfExists('restaurant_new_purchase_orders');
        Schema::dropIfExists('restaurant_new_supplier_quotation_lines');
        Schema::dropIfExists('restaurant_new_supplier_quotations');
    }
}
