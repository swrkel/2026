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
        Schema::create('variation_group_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('variation_id')->index('variation_group_prices_variation_id_foreign');
            $table->unsignedInteger('price_group_id')->index('price_group_id');
            $table->decimal('price_inc_tax', 22, 4);
            $table->timestamps();

            $table->index(['price_group_id'], 'variation_group_prices_price_group_id_foreign');
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
        Schema::dropIfExists('variation_group_prices');
    }
};
