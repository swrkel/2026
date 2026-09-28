<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('hm_guests', function(Blueprint $table){$table->id();$table->unsignedBigInteger('contact_id')->nullable()->index();$table->string('guest_code')->unique();$table->string('guest_name');$table->string('mobile')->nullable();$table->string('email')->nullable();$table->string('id_no')->nullable();$table->string('status')->default('active');$table->timestamps();$table->softDeletes();});
  Schema::create('hm_reservations', function(Blueprint $table){$table->id();$table->string('reservation_no')->unique();$table->foreignId('guest_id')->nullable()->index();$table->date('arrival_date')->index();$table->date('departure_date')->index();$table->integer('adults')->default(1);$table->integer('children')->default(0);$table->string('booking_source')->nullable();$table->string('status')->default('reserved');$table->decimal('estimated_total',22,4)->default(0);$table->timestamps();$table->softDeletes();});
  Schema::create('hm_reservation_rooms', function(Blueprint $table){$table->id();$table->foreignId('reservation_id')->index();$table->foreignId('room_id')->nullable()->index();$table->foreignId('room_type_id')->nullable()->index();$table->foreignId('rate_plan_id')->nullable()->index();$table->decimal('rate',22,4)->default(0);$table->date('stay_date')->nullable()->index();$table->string('status')->default('reserved');$table->timestamps();});
  Schema::create('hm_checkins', function(Blueprint $table){$table->id();$table->foreignId('reservation_id')->nullable()->index();$table->foreignId('guest_id')->nullable()->index();$table->foreignId('room_id')->nullable()->index();$table->dateTime('checked_in_at')->nullable();$table->unsignedBigInteger('checked_in_by')->nullable();$table->string('status')->default('checked_in');$table->timestamps();$table->softDeletes();});
  Schema::create('hm_checkouts', function(Blueprint $table){$table->id();$table->foreignId('checkin_id')->nullable()->index();$table->foreignId('reservation_id')->nullable()->index();$table->foreignId('room_id')->nullable()->index();$table->dateTime('checked_out_at')->nullable();$table->unsignedBigInteger('checked_out_by')->nullable();$table->decimal('final_amount',22,4)->default(0);$table->string('status')->default('checked_out');$table->timestamps();$table->softDeletes();});
 }
 public function down(): void { foreach(['hm_checkouts','hm_checkins','hm_reservation_rooms','hm_reservations','hm_guests'] as $t){Schema::dropIfExists($t);} }
};
