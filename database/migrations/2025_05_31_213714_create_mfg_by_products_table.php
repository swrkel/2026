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
        Schema::create('mfg_by_products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('mfg_recipe_id')->index('mfg_recipe_id');
            $table->integer('variation_id')->index('variation_id');
            $table->integer('quantity');
            $table->integer('production_cost');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mfg_by_products');
    }
};
