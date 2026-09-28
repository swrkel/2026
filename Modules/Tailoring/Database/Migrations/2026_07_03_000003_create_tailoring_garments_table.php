<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tailoring_garments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('garment_code')->nullable()->index();
            $table->string('name');
            $table->string('category')->nullable();
            $table->json('measurement_fields')->nullable();
            $table->decimal('standard_price', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tailoring_garments'); }
};
