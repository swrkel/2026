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
        Schema::create('distribution_daily_summary_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('sheet_no');
            $table->unsignedBigInteger('vehicle_id');
            $table->date('date');
            $table->decimal('starting_meter', 10, 2)->nullable();
            $table->decimal('closing_meter', 10, 2)->nullable();
            $table->unsignedBigInteger('sales_rep_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_daily_summary_sheets');
    }
};
