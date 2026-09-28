<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rn_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'location_id', 'key'], 'rn_settings_scope_key_unique');
        });

        Schema::create('rn_dining_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rn_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('dining_area_id')->nullable()->index();
            $table->string('name');
            $table->unsignedInteger('capacity')->default(0);
            $table->string('status')->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rn_tables');
        Schema::dropIfExists('rn_dining_areas');
        Schema::dropIfExists('rn_settings');
    }
};
