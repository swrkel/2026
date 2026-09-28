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
        Schema::create('mpcs_9c_form_settings', function (Blueprint $table) {
            $table->unsignedInteger('id')->index('id');
            $table->unsignedInteger('business_id');
            $table->date('date')->nullable();
            $table->string('starting_number', 200)->nullable();
            $table->string('ref_pre_form_number', 200)->nullable();
            $table->string('added_user', 200)->nullable();
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_9c_form_settings');
    }
};
