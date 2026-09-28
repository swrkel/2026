<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distribution_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name')->nullable();
            $table->string('customer_contact')->nullable();
            $table->text('customer_address')->nullable();
            $table->dateTime('date');
            $table->date('delivery_date')->nullable();
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('sales_order_no')->index();
            $table->string('loading_sheet_no')->nullable();
            $table->text('invoice_note')->nullable();
            $table->text('shipping_note')->nullable();
            $table->text('shipping_details')->nullable();
            $table->enum('shipping_status', ['ordered', 'packed', 'shipped', 'delivered', 'cancelled'])->default('ordered');
            $table->string('status', 30)->default('active');
            $table->decimal('total', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_sales_orders');
    }
};
