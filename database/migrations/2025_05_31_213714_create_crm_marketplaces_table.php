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
        Schema::create('crm_marketplaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->index('business_id');
            $table->string('marketplace')->nullable();
            $table->string('site_key')->nullable();
            $table->string('site_id')->nullable()->index('site_id');
            $table->text('assigned_users')->nullable();
            $table->integer('crm_source_id')->nullable()->index('crm_source_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_marketplaces');
    }
};
