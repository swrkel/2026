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
        Schema::create('crm_schedule_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('schedule_id')->index('crm_schedule_logs_schedule_id_foreign');
            $table->enum('log_type', ['call', 'sms', 'meeting', 'email'])->default('email');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('subject');
            $table->text('description')->nullable();
            $table->integer('created_by')->index();
            $table->timestamps();

            $table->index(['schedule_id'], 'schedule_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_schedule_logs');
    }
};
