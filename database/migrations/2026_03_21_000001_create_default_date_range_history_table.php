<?php

// Modified by Engr. Alex -- task 7889

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDefaultDateRangeHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('default_date_range_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('date_range_label', 150);   // human-readable label e.g. "Last 30 Days"
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index('business_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('default_date_range_history');
    }
}
