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
        Schema::table('distribution_districts', function (Blueprint $table) {
            $table->foreign(['province_id'], 'DistrictProvince')->references(['id'])->on('distribution_provinces')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('distribution_districts', function (Blueprint $table) {
            $table->dropForeign('DistrictProvince');
        });
    }
};
