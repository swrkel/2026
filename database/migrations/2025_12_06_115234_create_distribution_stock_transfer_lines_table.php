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
        Schema::create('distribution_stock_transfer_lines', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();

            $table->decimal('qty', 20, 4)->default(0);
            $table->decimal('unit_sale_price', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);

            $table->timestamps();

            // Indexes
            $table->index('stock_transfer_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_stock_transfer_lines');
    }
};
