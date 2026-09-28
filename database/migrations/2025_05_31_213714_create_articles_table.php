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
        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->text('title')->nullable();
            $table->text('content')->nullable();
            $table->boolean('published')->default(false);
            $table->integer('category_id');
            $table->integer('updated_by');
            $table->boolean('featured')->default(false);
            $table->integer('rate_total')->default(0);
            $table->integer('rate_helpful')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
};
