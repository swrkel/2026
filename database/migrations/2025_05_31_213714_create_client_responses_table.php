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
        Schema::create('client_responses', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('leads_id')->index('leads_id');
            $table->date('date');
            $table->date('follow_up_date');
            $table->text('response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_responses');
    }
};
