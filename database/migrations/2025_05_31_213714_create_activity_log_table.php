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
        if (Schema::hasTable('activity_log')) {
            return;
        }

        Schema::create('activity_log', function (Blueprint $table) {
            $table->increments('id');
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->integer('subject_id')->nullable()->index('subject_id');
            $table->string('subject_type')->nullable();
            $table->integer('causer_id')->nullable()->index('causer_id');
            $table->string('causer_type')->nullable();
            $table->text('properties')->nullable();
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
        Schema::dropIfExists('activity_log');
    }
};
