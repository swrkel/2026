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
        Schema::create('routes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('date');
            $table->string('route_name');
            $table->string('orignal_location')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('distance', 15, 6)->nullable();
            $table->decimal('rate', 15, 6)->nullable();
            $table->decimal('route_amount', 15, 6)->nullable();
            $table->decimal('driver_incentive', 15, 6)->nullable();
            $table->decimal('helper_incentive', 15, 6)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('trip_category')->nullable();
            $table->integer('delivered_to_acc')->nullable();
            $table->decimal('actual_distance', 10, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
};
