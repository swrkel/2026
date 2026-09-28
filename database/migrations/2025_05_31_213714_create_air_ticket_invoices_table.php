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
        if (Schema::hasTable('air_ticket_invoices')) {
            return;
        }

        Schema::create('air_ticket_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('transaction_id')->index('transaction_id');
            $table->integer('business_id')->index('business_id');
            $table->timestamps();
            $table->string('airticket_no');
            $table->string('customer_group');
            $table->string('customer');
            $table->string('airline');
            $table->string('airline_invoice_no');
            $table->string('airline_agent');
            $table->string('travel_mode');
            $table->string('departure_country');
            $table->string('departure_airport');
            $table->date('departure_date');
            $table->time('departure_time')->nullable();
            $table->string('transit');
            $table->string('transit_airport');
            $table->string('arrival_country');
            $table->string('arrival_airport');
            $table->date('arrival_date');
            $table->time('arrival_time');
            $table->time('total_time')->nullable();
            $table->time('transit_time')->nullable();
            $table->text('note')->nullable();
            $table->string('supplier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('air_ticket_invoices');
    }
};
