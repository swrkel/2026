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
        Schema::create('mfg_recipe_cost', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('recipe_id')->index('recipe_id');
            $table->integer('cost_id')->index('cost_id');
            $table->string('cost_type', 255);
            $table->string('cost_value', 255);
            $table->string('cost_total', 255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mfg_recipe_cost');
    }
};
