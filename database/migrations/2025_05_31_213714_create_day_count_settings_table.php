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
        Schema::create('day_count_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->time('day_counted_from');
            $table->time('time_till');
            $table->enum('ending_date_type', ['same_day', 'following_day']);
            $table->boolean('status')->nullable()->default(false);
            $table->unsignedInteger('created_by')->index('created_by');
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
        Schema::dropIfExists('day_count_settings');
    }
};
