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
        Schema::create('crm_schedule_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_id')->index('crm_schedule_users_schedule_id_foreign');
            $table->integer('user_id')->index();
            $table->timestamps();

            $table->index(['schedule_id'], 'schedule_id');
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
        Schema::dropIfExists('crm_schedule_users');
    }
};
