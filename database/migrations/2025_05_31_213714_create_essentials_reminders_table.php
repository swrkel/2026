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
        Schema::create('essentials_reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->integer('user_id')->index();
            $table->string('name');
            $table->date('date');
            $table->time('time');
            $table->time('end_time')->nullable();
            $table->enum('repeat', ['one_time', 'every_day', 'every_week', 'every_month']);
            $table->timestamps();

            $table->index(['business_id']);
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
        Schema::dropIfExists('essentials_reminders');
    }
};
