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
        Schema::create('visitor_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->tinyInteger('enable_required_name')->default(0);
            $table->tinyInteger('enable_required_address')->default(0);
            $table->tinyInteger('enable_required_district')->default(0);
            $table->tinyInteger('enable_required_town')->default(0);
            $table->tinyInteger('enable_add_district')->default(0);
            $table->tinyInteger('enable_add_town')->default(0);
            $table->integer('created_by');
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
        Schema::dropIfExists('visitor_settings');
    }
};
