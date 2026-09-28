<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
 public function up(): void{
  if(!Schema::hasTable('disnew_audit_checks')){Schema::create('disnew_audit_checks',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->string('check_key');$table->string('check_group',100)->index();$table->text('expected_value')->nullable();$table->boolean('is_required')->default(true);$table->timestamps();});}
  if(!Schema::hasTable('disnew_audit_results')){Schema::create('disnew_audit_results',function(Blueprint $table){$table->bigIncrements('id');$table->unsignedInteger('business_id')->nullable()->index();$table->unsignedInteger('user_id')->nullable();$table->string('check_type',100);$table->string('status',50)->default('pending')->index();$table->text('message')->nullable();$table->json('payload')->nullable();$table->timestamps();});}
 }
 public function down(): void{Schema::dropIfExists('disnew_audit_results');Schema::dropIfExists('disnew_audit_checks');}
};
