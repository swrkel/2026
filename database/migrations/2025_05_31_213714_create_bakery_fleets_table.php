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
        Schema::create('bakery_fleets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->string('code_for_vehicle');
            $table->string('location_id')->index('location_id');
            $table->string('vehicle_number');
            $table->string('vehicle_type');
            $table->string('vehicle_brand');
            $table->string('vehicle_model');
            $table->string('chassis_number');
            $table->string('engine_number');
            $table->string('battery_detail');
            $table->string('tyre_detail');
            $table->text('notes')->nullable();
            $table->unsignedInteger('income_account_id')->nullable()->index('income_account_id');
            $table->unsignedInteger('expense_account_id')->nullable()->index('expense_account_id');
            $table->string('starting_meter', 100)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('fuel_type_id')->nullable()->index('fuel_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bakery_fleets');
    }
};
