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
        Schema::create('hms_booking_extras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('transaction_id')->index('transaction_id');
            $table->integer('hms_extra_id')->index('hms_extra_id');
            $table->decimal('price', 22, 4)->default(0);
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
        Schema::dropIfExists('hms_booking_extras');
    }
};
