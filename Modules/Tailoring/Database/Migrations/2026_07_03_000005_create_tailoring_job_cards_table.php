<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tailoring_job_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('tailoring_order_id')->index();
            $table->unsignedBigInteger('tailoring_garment_id')->nullable()->index();
            $table->string('job_card_no')->unique();
            $table->json('measurements_snapshot')->nullable();
            $table->json('materials')->nullable();
            $table->string('assigned_to')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('order_received')->index();
            $table->json('workflow_log')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tailoring_job_cards'); }
};
