<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tailoring_measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('tailoring_customer_id')->index();
            $table->string('template_name')->nullable();
            $table->json('measurements')->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tailoring_measurements'); }
};
