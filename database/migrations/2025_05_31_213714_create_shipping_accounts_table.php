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
        Schema::create('shipping_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id')->index('business_id');
            $table->integer('expense');
            $table->integer('income');
            $table->integer('shipping_mode')->nullable();
            $table->integer('shipping_partner')->nullable();
            $table->integer('added_by');
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
        Schema::dropIfExists('shipping_accounts');
    }
};
