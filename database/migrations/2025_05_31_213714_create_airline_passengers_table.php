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
        Schema::create('airline_passengers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('invoice_id')->index('invoice_id');
            $table->string('name');
            $table->string('passport_number');
            $table->string('passport_image');
            $table->string('airline_itinerary');
            $table->string('airticket_no');
            $table->string('frequent_flyer_no');
            $table->string('child');
            $table->decimal('price', 10);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
            $table->enum('passenger_type', ['child', 'infant', 'adult'])->nullable();
            $table->date('expiry_date')->nullable();
            $table->longText('additional_services')->nullable();
            $table->float('amount', 10, 0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airline_passengers');
    }
};
