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
        Schema::create('fuel_tanks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->string('fuel_tank_number', 50)->default('');
            $table->string('fuel_type', 30)->default('');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('storage_volume', 255)->default('');
            $table->string('current_balance', 255)->default('');
            $table->boolean('bulk_tank')->default(false);
            $table->string('tank_manufacturer')->nullable();
            $table->text('tank_manufacturer_phone')->nullable();
            $table->decimal('tank_capacity', 15, 3)->nullable();
            $table->string('unit_name')->nullable();
            $table->unsignedInteger('user_id')->index('user_id');
            $table->date('transaction_date')->nullable();
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
        Schema::dropIfExists('fuel_tanks');
    }
};
