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
        Schema::create('pump_operator_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id');
            $table->unsignedBigInteger('pump_id');
            $table->dateTime('assigned_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pump_operator_mappings');
    }
};
