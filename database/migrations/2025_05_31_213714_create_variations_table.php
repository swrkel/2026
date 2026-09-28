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
        Schema::create('variations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->unsignedInteger('product_id')->index('product_id');
            $table->string('sub_sku')->nullable()->index();
            $table->unsignedInteger('product_variation_id')->index('product_variation_id');
            $table->integer('variation_value_id')->nullable()->index('variation_value_id');
            $table->string('default_purchase_price', 200)->nullable();
            $table->string('dpp_inc_tax', 200)->nullable();
            $table->string('profit_percent', 200)->nullable();
            $table->string('default_sell_price', 200)->nullable();
            $table->string('sell_price_inc_tax', 200)->nullable()->comment('Sell price including tax');
            $table->timestamps();
            $table->softDeletes();
            $table->text('combo_variations')->nullable()->comment('Contains the combo variation details');
            $table->text('default_multiple_unit_price')->nullable();

            $table->index(['product_id'], 'variations_product_id_foreign');
            $table->index(['product_variation_id'], 'variations_product_variation_id_foreign');
            $table->index(['variation_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variations');
    }
};
