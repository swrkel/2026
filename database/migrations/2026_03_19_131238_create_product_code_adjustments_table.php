<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCodeAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_code_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id');
            $table->string('original_code', 191);
            $table->string('adjusted_code', 191);
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps(); // This creates created_at and updated_at

            // Indexes
            $table->index('business_id', 'idx_business_id');
            $table->index('product_id', 'idx_product_id');
            $table->index('variation_id', 'idx_variation_id');
            $table->index('created_at', 'idx_created_at');

            // Foreign Keys
            $table->foreign('business_id')
                  ->references('id')
                  ->on('business')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('variation_id')
                  ->references('id')
                  ->on('variations')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_code_adjustments');
    }
}