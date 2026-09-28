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
        Schema::create('discountlevels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('data_time', 255);
            $table->string('sub_category', 255);
            $table->string('user', 255);
            $table->string('max_discount', 255);
            $table->unsignedInteger('user_id')->index('discountlevels_user_id_foreign');
            $table->timestamps();

            $table->index(['user_id'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discountlevels');
    }
};
