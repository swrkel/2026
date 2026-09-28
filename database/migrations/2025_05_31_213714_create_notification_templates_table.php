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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->string('template_for');
            $table->text('email_body')->nullable();
            $table->text('sms_body')->nullable();
            $table->string('subject')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->boolean('auto_send_sms')->default(false);
            $table->timestamps();
            $table->text('phone_nos')->nullable();
            $table->string('notification_category', 200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
};
