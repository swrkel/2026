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
        Schema::create('hms_room_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->integer('no_of_adult');
            $table->integer('no_of_child');
            $table->integer('max_occupancy');
            $table->string('amenities')->nullable();
            $table->text('description')->nullable();
            $table->integer('business_id')->index('business_id');
            $table->unsignedBigInteger('created_by');
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
        Schema::dropIfExists('hms_room_types');
    }
};
