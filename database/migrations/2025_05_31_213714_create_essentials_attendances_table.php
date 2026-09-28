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
        Schema::create('essentials_attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('employee_name', 50);
            $table->string('employee_code', 50);
            $table->integer('user_id')->index();
            $table->integer('business_id')->index('business_id');
            $table->dateTime('clock_in_time')->nullable();
            $table->dateTime('clock_out_time')->nullable();
            $table->integer('essentials_shift_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('clock_in_note')->nullable();
            $table->text('clock_out_note')->nullable();
            $table->text('clock_in_location')->nullable();
            $table->text('clock_out_location')->nullable();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['essentials_shift_id'], 'essentials_shift_id');
            $table->index(['user_id'], 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_attendances');
    }
};
