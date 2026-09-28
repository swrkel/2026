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
        Schema::create('essentials_holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('business_id')->index('business_id');
            $table->integer('location_id')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['location_id'], 'location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_holidays');
    }
};
