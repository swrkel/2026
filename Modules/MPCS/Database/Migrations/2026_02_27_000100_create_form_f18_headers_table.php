<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormF18HeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_f18_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('form_no');
            $table->unsignedInteger('from_location_id');
            $table->unsignedInteger('to_location_id');
            $table->date('form_date');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'form_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_f18_headers');
    }
}

