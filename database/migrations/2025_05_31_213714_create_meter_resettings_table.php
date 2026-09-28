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
        Schema::create('meter_resettings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->integer('meter_reset_ref_no');
            $table->string('date_and_time');
            $table->integer('pump_id')->index('pump_id');
            $table->decimal('last_meter', 15, 5);
            $table->decimal('new_reset_meter', 15, 5);
            $table->integer('created_by');
            $table->text('reason')->nullable();
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
        Schema::dropIfExists('meter_resettings');
    }
};
