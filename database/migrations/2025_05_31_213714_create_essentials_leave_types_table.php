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
        Schema::create('essentials_leave_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('leave_type');
            $table->integer('max_leave_count')->nullable();
            $table->enum('leave_count_interval', ['month', 'year', 'calendar_year'])->nullable();
            $table->integer('business_id')->index('business_id');
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
        Schema::dropIfExists('essentials_leave_types');
    }
};
