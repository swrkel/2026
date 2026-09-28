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
        Schema::create('pumps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('pump_name', 50)->default('');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->string('fuel_type', 255)->default('');
            $table->date('installation_date');
            $table->string('pump_no', 25)->default('');
            $table->string('image_link', 300)->default('');
            $table->string('storage_tank', 11)->nullable();
            $table->decimal('starting_meter', 13, 4)->default(0);
            $table->decimal('last_meter_reading', 10, 3)->default(0);
            $table->decimal('temp_meter_reading', 10, 3)->default(0);
            $table->decimal('pod_starting_meter', 15, 6)->nullable()->comment('pump operator dashboard field only');
            $table->decimal('pod_last_meter', 15, 6)->nullable()->comment('pump operator dashboard field only');
            $table->unsignedInteger('product_id')->index('product_id');
            $table->unsignedInteger('fuel_tank_id')->index('fuel_tank_id');
            $table->text('qty');
            $table->integer('checkk')->default(1);
            $table->string('testing', 255)->default('1');
            $table->date('transaction_date');
            $table->boolean('bulk_sale_meter')->default(false);
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
        Schema::dropIfExists('pumps');
    }
};
