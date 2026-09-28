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
        Schema::create('mfg_byproducts_list', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('recipe_id')->index('recipe_id');
            $table->integer('variation_id')->index('variation_id');
            $table->string('output_qty', 255);
            $table->integer('sub_unit_id')->index('sub_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mfg_byproducts_list');
    }
};
