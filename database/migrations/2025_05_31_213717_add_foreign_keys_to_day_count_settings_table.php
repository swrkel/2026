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
        Schema::table('day_count_settings', function (Blueprint $table) {
            $table->foreign(['created_by'], 'day_count_settings_ibfk_1')->references(['id'])->on('users')->onUpdate('NO ACTION')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('day_count_settings', function (Blueprint $table) {
            $table->dropForeign('day_count_settings_ibfk_1');
        });
    }
};
