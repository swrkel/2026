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
        Schema::create('repair_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('color')->nullable();
            $table->integer('sort_order')->nullable();
            $table->integer('business_id')->index('business_id');
            $table->timestamps();
            $table->text('email_body')->nullable();
            $table->text('email_subject')->nullable();
            $table->boolean('is_completed_status')->default(false);
            $table->text('sms_template')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repair_statuses');
    }
};
