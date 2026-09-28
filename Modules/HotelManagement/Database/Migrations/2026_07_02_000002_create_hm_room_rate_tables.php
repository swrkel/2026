<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('hm_room_types', function(Blueprint $table){$table->id();$table->string('type_code')->unique();$table->string('type_name');$table->integer('base_occupancy')->default(1);$table->integer('max_occupancy')->default(1);$table->decimal('base_rate',22,4)->default(0);$table->string('status')->default('active');$table->timestamps();$table->softDeletes();});
  Schema::create('hm_rooms', function(Blueprint $table){$table->id();$table->foreignId('hotel_id')->nullable()->index();$table->foreignId('building_id')->nullable()->index();$table->foreignId('wing_id')->nullable()->index();$table->foreignId('floor_id')->nullable()->index();$table->foreignId('room_type_id')->nullable()->index();$table->string('room_no')->index();$table->string('room_name')->nullable();$table->string('phone_extension')->nullable();$table->string('status')->default('available');$table->string('housekeeping_status')->default('clean');$table->timestamps();$table->softDeletes();});
  Schema::create('hm_room_features', function(Blueprint $table){$table->id();$table->foreignId('room_id')->index();$table->foreignId('amenity_id')->nullable()->index();$table->string('feature_name')->nullable();$table->timestamps();});
  Schema::create('hm_rate_plans', function(Blueprint $table){$table->id();$table->string('plan_code')->unique();$table->string('plan_name');$table->string('meal_plan')->nullable();$table->decimal('rate',22,4)->default(0);$table->string('status')->default('active');$table->timestamps();$table->softDeletes();});
  Schema::create('hm_seasons', function(Blueprint $table){$table->id();$table->string('season_name');$table->date('start_date');$table->date('end_date');$table->decimal('rate_multiplier',8,4)->default(1);$table->string('status')->default('active');$table->timestamps();$table->softDeletes();});
 }
 public function down(): void { foreach(['hm_seasons','hm_rate_plans','hm_room_features','hm_rooms','hm_room_types'] as $t){Schema::dropIfExists($t);} }
};
