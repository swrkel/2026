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
        Schema::create('dsr_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('date_time')->default('2023-12-07 02:02:47');
            $table->unsignedBigInteger('country_id')->index('country_id');
            $table->unsignedBigInteger('province_id')->index('province_id');
            $table->unsignedBigInteger('district_id')->index('district_id');
            $table->longText('areas');
            $table->unsignedBigInteger('fuel_provider_id')->index('fuel_provider_id');
            $table->unsignedBigInteger('product_id')->nullable()->index('product_id');
            $table->string('accumulative_sale')->nullable();
            $table->string('accumulative_purchase')->nullable();
            $table->string('dealer_number');
            $table->string('dealer_name');
            $table->integer('dsr_starting_number');
            $table->unsignedBigInteger('user_id')->index('user_id');
            $table->unsignedBigInteger('business_id')->nullable()->index('business_id');
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
        Schema::dropIfExists('dsr_settings');
    }
};
