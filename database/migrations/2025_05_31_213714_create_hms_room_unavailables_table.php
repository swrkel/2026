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
        Schema::create('hms_room_unavailables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('hms_rooms_id')->index('hms_rooms_id');
            $table->date('date_from');
            $table->date('date_to');
            $table->text('unavailable_type');
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
        Schema::dropIfExists('hms_room_unavailables');
    }
};
