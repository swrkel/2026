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
        Schema::create('vehicle_fuel_quota', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('vehicle_category_id', 100)->nullable()->index('vehicle_category_id');
            $table->string('vehicle_classification_id', 100)->nullable()->index('vehicle_classification_id');
            $table->string('fuel_litters_allowed', 100)->nullable();
            $table->string('re_fill_cycle_in_hrs', 100)->nullable();
            $table->dateTime('date')->nullable();
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
        Schema::dropIfExists('vehicle_fuel_quota');
    }
};
