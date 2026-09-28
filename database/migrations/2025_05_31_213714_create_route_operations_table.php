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
        Schema::create('route_operations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('amt_method')->nullable();
            $table->integer('is_vat')->default(0);
            $table->unsignedInteger('business_id');
            $table->date('date_of_operation');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('route_id');
            $table->unsignedInteger('fleet_id');
            $table->string('invoice_no');
            $table->string('product_id', 255)->nullable();
            $table->string('order_number')->nullable();
            $table->date('order_date')->nullable();
            $table->text('qty')->nullable();
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('helper_id');
            $table->decimal('distance', 15, 6);
            $table->decimal('amount', 15, 6);
            $table->decimal('driver_incentive', 15, 6);
            $table->decimal('helper_incentive', 15, 6);
            $table->integer('transaction_id')->nullable();
            $table->double('starting_meter')->default(0);
            $table->double('ending_meter')->default(0);
            $table->double('actual_meter')->default(0);
            $table->integer('actual_meter_added_by')->default(0);
            $table->timestamp('actual_meter_added_on')->nullable();
            $table->timestamp('trip_completed_on')->nullable();
            $table->boolean('is_updated_actual_meter')->default(false);
            $table->text('notes');
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('vat_applied')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_operations');
    }
};
