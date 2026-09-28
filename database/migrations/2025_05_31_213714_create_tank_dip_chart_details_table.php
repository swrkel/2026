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
        Schema::create('tank_dip_chart_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tank_dip_chart_id')->index('tank_dip_chart_id');
            $table->decimal('dip_reading', 15, 3);
            $table->decimal('dip_reading_value', 15, 3);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tank_dip_chart_details');
    }
};
