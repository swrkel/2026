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
        Schema::create('user_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index('user_id');
            $table->string('access_type', 50)->index('access_type');
            $table->integer('access_time')->index('access_time');
            $table->string('country', 200);
            $table->string('state', 200);
            $table->string('city', 200);
            $table->string('timezone', 200);
            $table->float('latitude', 10, 0);
            $table->float('longitude', 10, 0);
            $table->string('zip_code', 200)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('constituency', 100)->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('sublocality', 100)->nullable();
            $table->string('route', 100)->nullable();
            $table->string('landmark', 100)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('location_data_source', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_locations');
    }
};
