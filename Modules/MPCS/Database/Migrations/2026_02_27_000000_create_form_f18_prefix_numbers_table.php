<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF18PrefixNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_f18_prefix_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->date('opening_date');
            $table->string('prefix')->nullable();
            $table->unsignedInteger('starting_number');
            $table->json('transferred_locations');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'opening_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f18_prefix_numbers');
    }
}

