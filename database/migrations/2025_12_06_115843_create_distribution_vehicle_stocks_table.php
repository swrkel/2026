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
        Schema::create('distribution_vehicle_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();

            $table->decimal('qty', 20, 4)->default(0);

            $table->timestamps();

            $table->unique(['business_id', 'vehicle_id', 'product_id', 'variation_id'], 'vehicle_stock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_vehicle_stocks');
    }
};
