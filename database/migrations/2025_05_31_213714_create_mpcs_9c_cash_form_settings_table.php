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
        Schema::create('mpcs_9c_cash_form_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->timestamp('date_time')->useCurrentOnUpdate()->useCurrent();
            $table->integer('starting_number');
            $table->integer('ref_pre_form_number');
            $table->string('added_user', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_9c_cash_form_settings');
    }
};
