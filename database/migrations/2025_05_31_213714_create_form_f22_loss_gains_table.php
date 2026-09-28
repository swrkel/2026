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
        Schema::create('form_f22_loss_gains', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->integer('stock_loss_account');
            $table->integer('stock_gain_account');
            $table->string('status', 30);
            $table->string('added_user', 30);
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
        Schema::dropIfExists('form_f22_loss_gains');
    }
};
