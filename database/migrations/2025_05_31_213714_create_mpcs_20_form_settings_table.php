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
        Schema::create('mpcs_20_form_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('business_id', 255);
            $table->date('opening_date');
            $table->string('starting_number', 255);
            $table->string('total_sale', 255);
            $table->string('cash_sale', 255);
            $table->string('credit_sale', 255);
            $table->string('category', 255);
            $table->integer('created_by');
            $table->string('info', 255)->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mpcs_20_form_settings');
    }
};
