<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(){
  if(!Schema::hasTable('leads_new_campaigns')) Schema::create('leads_new_campaigns',function(Blueprint $t){$t->id();$t->unsignedBigInteger('business_id')->nullable()->index();$t->string('name');$t->string('code')->nullable();$t->date('start_date')->nullable();$t->date('end_date')->nullable();$t->decimal('budget',22,4)->default(0);$t->decimal('cost',22,4)->default(0);$t->decimal('revenue',22,4)->default(0);$t->boolean('is_active')->default(1);$t->unsignedBigInteger('created_by')->nullable();$t->unsignedBigInteger('updated_by')->nullable();$t->timestamps();});
  if(!Schema::hasTable('leads_new_territories')) Schema::create('leads_new_territories',function(Blueprint $t){$t->id();$t->unsignedBigInteger('business_id')->nullable()->index();$t->string('name');$t->string('code')->nullable();$t->text('description')->nullable();$t->boolean('is_active')->default(1);$t->timestamps();});
  if(!Schema::hasTable('leads_new_targets')) Schema::create('leads_new_targets',function(Blueprint $t){$t->id();$t->unsignedBigInteger('business_id')->nullable()->index();$t->unsignedBigInteger('user_id')->nullable()->index();$t->date('period_start')->nullable();$t->date('period_end')->nullable();$t->integer('lead_target')->default(0);$t->decimal('revenue_target',22,4)->default(0);$t->timestamps();});
  if(!Schema::hasTable('leads_new_sla_rules')) Schema::create('leads_new_sla_rules',function(Blueprint $t){$t->id();$t->unsignedBigInteger('business_id')->nullable()->index();$t->string('name');$t->json('rules')->nullable();$t->boolean('is_active')->default(1);$t->timestamps();});
 }
 public function down(){ Schema::dropIfExists('leads_new_sla_rules'); Schema::dropIfExists('leads_new_targets'); Schema::dropIfExists('leads_new_territories'); Schema::dropIfExists('leads_new_campaigns'); }
};
