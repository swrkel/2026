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
        Schema::create('leads_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->date('date')->nullable();
            $table->string('quotations', 100)->nullable();
            $table->string('sales_inv', 100)->nullable();
            $table->string('clients_res', 255)->nullable();
            $table->string('action', 255)->nullable();
            $table->string('user', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads_settings');
    }
};
