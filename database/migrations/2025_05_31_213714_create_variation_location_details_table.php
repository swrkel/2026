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
        Schema::create('variation_location_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('product_variation_id')->index('product_variation_id')->comment('id from product_variations table');
            $table->unsignedInteger('variation_id')->index('variation_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->decimal('qty_available', 22, 4)->nullable();
            $table->timestamps();

            $table->index(['location_id'], 'variation_location_details_location_id_foreign');
            $table->index(['product_id']);
            $table->index(['product_variation_id']);
            $table->index(['variation_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_location_details');
    }
};
