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
        Schema::create('patient_sugar_readings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('member_id');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('sugar_reading_breakfast');
            $table->integer('sugar_reading_lunch');
            $table->integer('sugar_reading_dinner');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_sugar_readings');
    }
};
