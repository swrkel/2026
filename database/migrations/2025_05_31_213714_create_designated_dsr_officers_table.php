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
        Schema::create('designated_dsr_officers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('fuel_provider_id')->nullable()->index('fuel_provider_id');
            $table->string('country_id', 200)->nullable()->index('country_id');
            $table->string('province_id', 200)->nullable()->index('province_id');
            $table->string('district_id', 200)->nullable()->index('district_id');
            $table->unsignedBigInteger('business_id')->nullable()->index('business_id');
            $table->longText('areas')->nullable();
            $table->string('officer_name')->nullable();
            $table->string('officer_mobile')->nullable();
            $table->string('officer_username')->nullable();
            $table->string('officer_password')->nullable();
            $table->enum('state', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('designated_dsr_officers');
    }
};
