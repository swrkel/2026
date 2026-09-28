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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 255)->nullable();
            $table->string('number', 25)->nullable();
            $table->integer('fuel_type_id')->nullable()->index('fuel_type_id');
            $table->integer('vehicle_category_id')->nullable()->index('vehicle_category_id');
            $table->string('district_id', 255)->nullable()->index('district_id');
            $table->string('mobile', 12)->nullable();
            $table->string('landline', 255)->nullable();
            $table->string('town', 255)->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('passcode')->nullable();
            $table->string('password', 255)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
