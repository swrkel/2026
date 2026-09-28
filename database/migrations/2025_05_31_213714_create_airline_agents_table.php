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
        if (Schema::hasTable('airline_agents')) {
            return;
        }

        Schema::create('airline_agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('business_id')->nullable()->index('business_id');
            $table->unsignedBigInteger('user_id')->index('user_id');
            $table->bigInteger('contact_id')->nullable();
            $table->string('agent');
            $table->string('address')->nullable();
            $table->string('mobile_1')->nullable();
            $table->string('mobile_2')->nullable();
            $table->string('land_no')->nullable();
            $table->date('joined_date')->nullable();
            $table->double('opening_balance', 8, 2)->nullable();
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
        Schema::dropIfExists('airline_agents');
    }
};
