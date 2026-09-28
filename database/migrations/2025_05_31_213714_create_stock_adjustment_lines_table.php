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
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('transaction_id')->index();
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('variation_id')->index('stock_adjustment_lines_variation_id_foreign');
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_price', 22, 4)->nullable()->comment('Last purchase unit price');
            $table->string('type', 20)->nullable();
            $table->string('stock_adjustment_type', 20)->nullable();
            $table->integer('removed_purchase_line')->nullable();
            $table->integer('lot_no_line_id')->nullable()->index('lot_no_line_id');
            $table->unsignedInteger('tank_id')->nullable()->index('tank_id');
            $table->unsignedInteger('inventory_adjustment_account')->nullable();
            $table->timestamps();

            $table->index(['product_id'], 'stock_adjustment_lines_product_id_foreign');
            $table->index(['transaction_id'], 'transaction_id');
            $table->index(['variation_id'], 'variation_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_adjustment_lines');
    }
};
