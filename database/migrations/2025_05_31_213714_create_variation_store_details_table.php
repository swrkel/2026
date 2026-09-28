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
        Schema::create('variation_store_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('product_variation_id')->index('product_variation_id')->comment('id from product_variations table');
            $table->unsignedInteger('variation_id')->index('variation_id');
            $table->integer('store_id')->index('store_id');
            $table->decimal('qty_available', 16, 4)->nullable();
            $table->timestamps();

            $table->index(['product_id'], 'variation_location_details_product_id_index');
            $table->index(['product_variation_id'], 'variation_location_details_product_variation_id_index');
            $table->index(['variation_id'], 'variation_location_details_variation_id_index');
            $table->index(['store_id'], 'variation_store_details_store_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_store_details');
    }
};
