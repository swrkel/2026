<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('hm_hotels', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('business_location_id')->nullable()->index();
            $table->string('hotel_code')->unique(); $table->string('hotel_name'); $table->string('star_rating')->nullable(); $table->text('address')->nullable();
            $table->string('phone')->nullable(); $table->string('email')->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('hm_buildings', function (Blueprint $table) {$table->id(); $table->foreignId('hotel_id')->nullable()->index(); $table->string('building_code'); $table->string('building_name'); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();});
        Schema::create('hm_wings', function (Blueprint $table) {$table->id(); $table->foreignId('hotel_id')->nullable()->index(); $table->foreignId('building_id')->nullable()->index(); $table->string('wing_code'); $table->string('wing_name'); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();});
        Schema::create('hm_floors', function (Blueprint $table) {$table->id(); $table->foreignId('hotel_id')->nullable()->index(); $table->foreignId('building_id')->nullable()->index(); $table->foreignId('wing_id')->nullable()->index(); $table->string('floor_code'); $table->string('floor_name'); $table->integer('floor_no')->default(0); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();});
        Schema::create('hm_amenities', function (Blueprint $table) {$table->id(); $table->string('amenity_code')->unique(); $table->string('amenity_name'); $table->string('icon')->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->softDeletes();});
    }
    public function down(): void { foreach(['hm_amenities','hm_floors','hm_wings','hm_buildings','hm_hotels'] as $t){ Schema::dropIfExists($t); } }
};
