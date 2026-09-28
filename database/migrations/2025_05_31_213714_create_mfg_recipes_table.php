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
        Schema::create('mfg_recipes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->index();
            $table->integer('variation_id')->index();
            $table->text('instructions')->nullable();
            $table->decimal('waste_percent', 10)->default(0);
            $table->decimal('ingredients_cost', 22, 4)->default(0);
            $table->decimal('extra_cost', 22, 4)->default(0);
            $table->decimal('total_quantity', 22, 4)->default(0);
            $table->decimal('final_price', 22, 4);
            $table->integer('sub_unit_id')->nullable()->index('sub_unit_id');
            $table->timestamps();
            $table->string('production_cost_type')->nullable()->default('percentage');
            $table->string('by_product_available', 255)->default('no');

            $table->index(['product_id'], 'product_id');
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
        Schema::dropIfExists('mfg_recipes');
    }
};
