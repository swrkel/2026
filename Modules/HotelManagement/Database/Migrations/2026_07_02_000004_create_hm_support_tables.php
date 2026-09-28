<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('hm_audit_logs', function(Blueprint $table){$table->id();$table->unsignedBigInteger('business_id')->nullable()->index();$table->unsignedBigInteger('user_id')->nullable()->index();$table->string('action')->index();$table->string('model_type')->nullable();$table->unsignedBigInteger('model_id')->nullable();$table->json('payload')->nullable();$table->timestamps();});
  Schema::create('hm_settings', function(Blueprint $table){$table->id();$table->unsignedBigInteger('business_id')->nullable()->index();$table->string('key')->index();$table->text('value')->nullable();$table->timestamps();$table->unique(['business_id','key']);});
 }
 public function down(): void { Schema::dropIfExists('hm_settings'); Schema::dropIfExists('hm_audit_logs'); }
};
