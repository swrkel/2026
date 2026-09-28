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
        Schema::create('dip_readings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('ref_number')->nullable();
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('tank_id')->index('tank_id');
            $table->string('date_and_time');
            $table->decimal('dip_reading', 15, 5);
            $table->date('transaction_date');
            $table->decimal('fuel_balance_dip_reading', 15, 5);
            $table->decimal('current_qty', 15, 5);
            $table->string('tank_manufacturer')->nullable();
            $table->decimal('tank_capacity', 15, 3)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('reset_new_dip', 222)->nullable();
            $table->timestamp('daily_report_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dip_readings');
    }
};
