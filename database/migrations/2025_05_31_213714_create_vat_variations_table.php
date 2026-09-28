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
        Schema::create('vat_variations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index('variations_name_index');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->string('sub_sku')->nullable()->index('variations_sub_sku_index');
            $table->unsignedInteger('product_variation_id')->index('product_variation_id');
            $table->integer('variation_value_id')->nullable()->index('variation_value_id');
            $table->decimal('default_purchase_price', 30, 6)->nullable();
            $table->decimal('dpp_inc_tax', 30, 6)->default(0);
            $table->decimal('profit_percent', 22, 4)->default(0);
            $table->decimal('default_sell_price', 30)->nullable();
            $table->decimal('sell_price_inc_tax', 30, 6)->nullable()->comment('Sell price including tax');
            $table->timestamps();
            $table->softDeletes();
            $table->text('combo_variations')->nullable()->comment('Contains the combo variation details');
            $table->text('default_multiple_unit_price')->nullable();

            $table->index(['product_id'], 'variations_product_id_foreign');
            $table->index(['product_variation_id'], 'variations_product_variation_id_foreign');
            $table->index(['variation_value_id'], 'variations_variation_value_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_variations');
    }
};
