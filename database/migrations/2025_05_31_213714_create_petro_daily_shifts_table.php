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
        Schema::create('petro_daily_shifts', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('pump_operator_pending', 100);
            $table->string('pump_operator_assigned', 100);
            $table->string('shift_no', 11);
            $table->dateTime('date');
            $table->time('time');
            $table->integer('user');
            $table->integer('status');
            $table->dateTime('updated_at');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('petro_daily_shifts');
    }
};
