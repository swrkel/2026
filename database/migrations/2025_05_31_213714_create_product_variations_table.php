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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('variation_template_id')->nullable()->index('variation_template_id');
            $table->string('name')->index();
            $table->unsignedInteger('product_id')->index('product_id');
            $table->boolean('is_dummy')->default(true);
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variations');
    }
};
