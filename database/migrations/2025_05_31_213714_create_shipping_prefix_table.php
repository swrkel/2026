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
        Schema::create('shipping_prefix', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id')->index('business_id');
            $table->string('created_by');
            $table->string('added_date');
            $table->string('prefix');
            $table->integer('starting_no');
            $table->integer('shipping_mode');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('shipping_prefix');
    }
};
