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
        Schema::create('helpguide_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('thumbnail')->nullable();
            $table->integer('category_order')->default(0);
            $table->boolean('active')->default(false);
            $table->boolean('has_ticket')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('parent_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('helpguide_categories');
    }
};
