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
        Schema::create('price_change_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('gain_account_id')->nullable()->index('gain_account_id');
            $table->integer('loss_account_id')->nullable()->index('loss_account_id');
            $table->date('date')->nullable();
            $table->integer('business_id')->nullable()->index('business_id');
            $table->integer('user')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('price_change_settings');
    }
};
