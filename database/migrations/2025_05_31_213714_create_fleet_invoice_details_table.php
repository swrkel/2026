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
        Schema::create('fleet_invoice_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('invoice_id')->index('invoice_id');
            $table->date('date');
            $table->string('location');
            $table->string('invoice_no')->nullable();
            $table->string('product');
            $table->string('qty', 250);
            $table->string('vehicle_number', 100);
            $table->decimal('mileage', 10);
            $table->decimal('invoice_amount', 15);
            $table->text('payment_details');
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
        Schema::dropIfExists('fleet_invoice_details');
    }
};
