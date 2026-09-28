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
        Schema::create('essentials_shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->enum('type', ['fixed_shift', 'flexible_shift'])->default('fixed_shift')->index();
            $table->integer('business_id')->index('business_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_allowed_auto_clockout')->default(false);
            $table->time('auto_clockout_time')->nullable();
            $table->text('holidays')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_shifts');
    }
};
