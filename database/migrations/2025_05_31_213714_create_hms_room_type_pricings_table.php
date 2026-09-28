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
        Schema::create('hms_room_type_pricings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('hms_room_type_id')->index('hms_room_type_id');
            $table->string('season_type');
            $table->double('default_price_per_night')->nullable();
            $table->decimal('adults')->nullable();
            $table->decimal('childrens')->nullable();
            $table->double('price_monday')->nullable();
            $table->double('price_tuesday')->nullable();
            $table->double('price_wednesday')->nullable();
            $table->double('price_thursday')->nullable();
            $table->double('price_friday')->nullable();
            $table->double('price_saturday')->nullable();
            $table->double('price_sunday')->nullable();
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
        Schema::dropIfExists('hms_room_type_pricings');
    }
};
