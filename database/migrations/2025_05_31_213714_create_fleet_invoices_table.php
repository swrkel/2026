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
        Schema::create('fleet_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('original_location')->nullable();
            $table->unsignedInteger('customer_id')->nullable()->index('customer_id');
            $table->integer('location_id')->index('location_id');
            $table->integer('invoice_name');
            $table->integer('logo');
            $table->date('print_date');
            $table->date('date_from');
            $table->date('date_to');
            $table->unsignedInteger('added_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('type')->default('dynamic');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fleet_invoices');
    }
};
