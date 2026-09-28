<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distribution_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');

            $table->timestamp('date_time')->useCurrent();   // auto datetime

            $table->string('vehicle_no');
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_brand')->nullable();
            $table->string('vehicle_model')->nullable();

            $table->date('revenue_license_renewal_date')->nullable();
            $table->integer('starting_meter')->default(0);

            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'vehicle_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_vehicles');
    }
};
