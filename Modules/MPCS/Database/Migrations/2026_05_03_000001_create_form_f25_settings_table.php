<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF25SettingsTable extends Migration
{
    public function up()
    {
        Schema::create('mpcs_f25_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->date('opening_date');
            $table->unsignedInteger('starting_number');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'opening_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mpcs_f25_settings');
    }
}

