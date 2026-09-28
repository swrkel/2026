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
        Schema::create('visits', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('visitor_id')->index('visitor_id');
            $table->unsignedInteger('business_id')->index('business_id')->comment('business which visitor visit');
            $table->integer('no_of_accompanied')->default(0);
            $table->string('unique_code')->nullable();
            $table->dateTime('date_and_time');
            $table->date('visited_date');
            $table->timestamp('logged_in_time')->nullable();
            $table->timestamp('logged_out_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visits');
    }
};
