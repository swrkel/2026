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
        Schema::create('fleet_fuel_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fleet_id')->index('fleet_id');
            $table->integer('driver_id')->index('driver_id');
            $table->integer('business_id')->index('business_id');
            $table->timestamp('date_of_operation')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('previous_odometer', 15, 3);
            $table->decimal('current_odometer', 15, 3);
            $table->integer('fuel_type');
            $table->integer('liters');
            $table->decimal('price_per_liter', 15, 3);
            $table->decimal('total_amount', 15, 3);
            $table->decimal('fuel_cost', 10, 3);
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fleet_fuel_details');
    }
};
