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
        Schema::create('shipping_invoice_send', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id');
            $table->integer('shipment_id');
            $table->string('created_by');
            $table->string('whatsapp_number', 255)->nullable();
            $table->string('email_id', 255)->nullable();
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
        Schema::dropIfExists('shipping_invoice_send');
    }
};
