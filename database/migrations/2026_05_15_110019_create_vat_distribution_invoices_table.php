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
        Schema::create('vat_distribution_invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->index();
            $table->integer('customer_id')->index();
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_contact')->nullable();
            $table->string('customer_vat_no')->nullable();
            $table->dateTime('date');
            $table->date('delivery_date')->nullable();
            $table->integer('sales_rep_id')->nullable();
            $table->integer('route_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('loading_sheet_no')->nullable();
            $table->string('place_of_supply')->nullable();
            $table->text('additional_info')->nullable();
            $table->text('invoice_note')->nullable();
            $table->text('shipping_note')->nullable();
            $table->text('shipping_details')->nullable();
            $table->string('shipping_status')->default('ordered');
            $table->string('status')->default('active');
            $table->integer('sales_order_id')->nullable();
            $table->decimal('total', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('grand_total', 22, 4)->default(0);
            $table->decimal('payment_cash', 22, 4)->default(0);
            $table->decimal('payment_card', 22, 4)->default(0);
            $table->decimal('payment_credit', 22, 4)->default(0);
            $table->decimal('payment_cheque', 22, 4)->default(0);
            $table->decimal('payment_total', 22, 4)->default(0);
            $table->integer('added_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('vat_distribution_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id')->index();
            $table->integer('product_id')->index();
            $table->integer('unit_id')->nullable();
            $table->decimal('qty', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('final_amount', 22, 4)->default(0);
            $table->boolean('is_free')->default(0);
            $table->boolean('is_free_bottles')->default(0);
            $table->boolean('is_free_auto')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vat_distribution_invoice_lines');
        Schema::dropIfExists('vat_distribution_invoices');
    }
};
