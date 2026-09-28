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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->nullable();
            $table->boolean('re_captcha_enabled')->nullable()->default(true);
            $table->dateTime('re_captcha_enabled_date')->nullable();
            $table->boolean('verification_done')->nullable()->default(false);
            $table->boolean('opt_verification_enabled')->nullable()->default(false);
            $table->integer('verification_attempt_count')->nullable();
            $table->dateTime('opt_verification_enabled_date')->nullable();
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
        Schema::dropIfExists('user_settings');
    }
};
