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
        Schema::create('business_dsr_officers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('business_ids');
            $table->integer('dsr_officer_id')->index('dsr_officer_id');
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
        Schema::dropIfExists('business_dsr_officers');
    }
};
