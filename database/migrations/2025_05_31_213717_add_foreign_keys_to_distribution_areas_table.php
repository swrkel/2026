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
        Schema::table('distribution_areas', function (Blueprint $table) {
            $table->foreign(['district_id'], 'AreaDistrict')->references(['id'])->on('distribution_districts')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_areas', function (Blueprint $table) {
            $table->dropForeign('AreaDistrict');
        });
    }
};
