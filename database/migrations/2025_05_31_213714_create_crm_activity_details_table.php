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
        Schema::create('crm_activity_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('crm_activity_id')->index('crm_activity_id');
            $table->date('date');
            $table->dateTime('next_follow_up_date');
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_activity_details');
    }
};
