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
        Schema::create('distribution_vehicle_meters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');

            $table->unsignedBigInteger('daily_summary_id'); 
            $table->unsignedBigInteger('vehicle_id');

            $table->date('date');  
            $table->string('daily_summary_sheet_no');

            $table->integer('starting_meter');
            $table->integer('closing_meter');

            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();

            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();

            $table->index('vehicle_id');
            $table->index('daily_summary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_vehicle_meters');
    }
};
