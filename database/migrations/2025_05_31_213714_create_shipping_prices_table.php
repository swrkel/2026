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
        Schema::create('shipping_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id')->index('business_id');
            $table->string('created_by');
            $table->string('added_date');
            $table->integer('shipping_package');
            $table->string('shipping_partner');
            $table->decimal('constant_value', 10, 3);
            $table->integer('shipping_mode');
            $table->decimal('per_kg', 10, 3);
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->integer('fixed_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_prices');
    }
};
