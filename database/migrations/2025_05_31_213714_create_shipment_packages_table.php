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
        Schema::create('shipment_packages', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('shipment_id')->index('shipment_id');
            $table->string('package_name')->nullable();
            $table->text('package_description')->nullable();
            $table->decimal('length', 22)->nullable();
            $table->decimal('width', 22)->nullable();
            $table->decimal('height', 22)->nullable();
            $table->decimal('weight', 22, 5)->nullable();
            $table->decimal('rate_per_kg', 22, 5)->nullable();
            $table->decimal('volumetric_weight', 22, 5)->nullable();
            $table->string('price_type')->nullable();
            $table->decimal('shipping_charge', 22, 5)->nullable();
            $table->decimal('declared_value', 22, 5)->nullable();
            $table->decimal('service_fee', 22, 5)->nullable();
            $table->decimal('total', 22, 5)->nullable();
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
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
        Schema::dropIfExists('shipment_packages');
    }
};
