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
        Schema::create('distribution_sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->decimal('qty', 22, 4)->default(0);
            $table->decimal('unit_price', 22, 4)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('discount', 22, 4)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('final_amount', 22, 4)->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_free_bottles')->default(false);
            $table->boolean('is_free_auto')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_sales_order_lines');
    }
};
