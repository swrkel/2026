<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vat_purchase_products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('purchase_id');
            $table->integer('product_id');
            $table->decimal('purchase_qty', 15, 3);
            $table->decimal('free_qty', 15, 3);
            $table->decimal('unit_before_discount', 15, 3);
            $table->decimal('discount', 10, 3);
            $table->decimal('unit_cost', 15, 3);
            $table->decimal('subtotal_before_tax', 15, 3);
            $table->integer('tax_id')->nullable();
            $table->decimal('tax_amount', 15, 3);
            $table->decimal('net_cost', 15, 3);
            $table->decimal('line_total', 15, 3);
            $table->decimal('profit_margin', 10, 3);
            $table->decimal('unit_selling_price', 15, 3);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_purchase_products');
    }
};
