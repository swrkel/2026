<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormF22PumpMetersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_f22_pump_meters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('header_id')->unsigned();
            $table->integer('pump_id')->unsigned();
            $table->string('pump_name')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('meter_reading', 22, 4)->default(0);
            $table->timestamps();
            
            $table->foreign('header_id')->references('id')->on('form_f22_headers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f22_pump_meters');
    }
}
