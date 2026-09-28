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
        Schema::create('shipping_bar_qr_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id')->index('business_id');
            $table->string('created_by');
            $table->string('details', 255);
            $table->integer('bar_code');
            $table->integer('qr_code');
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
        Schema::dropIfExists('shipping_bar_qr_code');
    }
};
