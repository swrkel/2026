<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bs_service_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('bs_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('service_code')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('buffer_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('bs_service_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedInteger('business_location_id')->nullable()->index();
            $table->decimal('price', 22, 4)->default(0);
            $table->boolean('discount_allowed')->default(true);
            $table->boolean('commission_applicable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('bs_service_prices');
        Schema::dropIfExists('bs_services');
        Schema::dropIfExists('bs_service_categories');
    }
};
