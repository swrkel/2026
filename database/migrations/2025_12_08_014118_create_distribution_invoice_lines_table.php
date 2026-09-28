<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('distribution_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 22, 4);
            $table->decimal('unit_price', 22, 4);
            $table->decimal('amount', 22, 4);
            $table->decimal('discount', 22, 4)->default(0);
            $table->decimal('final_amount', 22, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_invoice_lines');
    }
};
