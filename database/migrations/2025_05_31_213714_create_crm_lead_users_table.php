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
        Schema::create('crm_lead_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('contact_id')->index('contact_id');
            $table->integer('user_id')->index();

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
        Schema::dropIfExists('crm_lead_users');
    }
};
