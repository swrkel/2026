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
        Schema::create('crm_call_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->index('business_id');
            $table->integer('user_id')->nullable()->index();
            $table->string('call_type')->nullable();
            $table->string('mobile_number');
            $table->string('mobile_name')->nullable();
            $table->integer('contact_id')->nullable()->index('contact_id');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('created_by')->index();
            $table->timestamps();

            $table->index(['business_id']);
            $table->index(['contact_id']);
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
        Schema::dropIfExists('crm_call_logs');
    }
};
