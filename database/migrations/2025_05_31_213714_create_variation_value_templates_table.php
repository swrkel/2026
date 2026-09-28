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
        Schema::create('variation_value_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->index();
            $table->unsignedInteger('variation_template_id')->index('variation_template_id');
            $table->timestamps();

            $table->index(['variation_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_value_templates');
    }
};
