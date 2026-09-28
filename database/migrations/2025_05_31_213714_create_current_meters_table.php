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
        Schema::create('current_meters', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->dateTime('date_and_time');
            $table->unsignedInteger('pump_operator_id')->index('pump_operator_id');
            $table->unsignedInteger('pump_id')->index('pump_id');
            $table->string('pump_no');
            $table->decimal('starting_meter', 15, 4);
            $table->decimal('last_time_meter', 15, 4);
            $table->decimal('current_meter', 15, 4);
            $table->decimal('sale_price', 15, 4);
            $table->decimal('sold_ltr', 15, 4);
            $table->decimal('amount', 15, 4);
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('date')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_meters');
    }
};
