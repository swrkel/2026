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
        Schema::create('distribution_stock_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('business_id');

            $table->date('date');
            $table->integer('reference_no'); // Auto-increment (custom handled)
            
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('vehicle_id');

            $table->unsignedBigInteger('product_category_id')->nullable();

            $table->text('note')->nullable();

            $table->unsignedBigInteger('created_by');
            
            $table->timestamps();

            // Indexes
            $table->index(['business_id', 'reference_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_stock_transfers');
    }
};
