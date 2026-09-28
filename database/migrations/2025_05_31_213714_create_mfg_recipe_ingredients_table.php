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
        Schema::create('mfg_recipe_ingredients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mfg_recipe_id')->index('mfg_recipe_id');
            $table->integer('variation_id')->index('variation_id');
            $table->integer('mfg_ingredient_group_id')->nullable()->index('mfg_ingredient_group_id');
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('waste_percent', 22, 4)->default(0);
            $table->integer('sub_unit_id')->nullable()->index('sub_unit_id');
            $table->timestamps();
            $table->integer('sort_order')->nullable();

            $table->index(['mfg_recipe_id'], 'mfg_recipe_ingredients_mfg_recipe_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mfg_recipe_ingredients');
    }
};
