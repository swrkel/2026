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
        Schema::create('new_vehicle', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('customer_id', 255)->nullable()->index('customer_id');
            $table->string('vehicle_no', 255)->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
            $table->integer('business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('new_vehicle');
    }
};
