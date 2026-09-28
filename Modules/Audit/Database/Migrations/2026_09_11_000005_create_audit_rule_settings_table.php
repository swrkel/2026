<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
class CreateAuditRuleSettingsTable extends Migration
{public function up(){if(!Schema::hasTable('audit_rule_settings')) Schema::create('audit_rule_settings',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('business_id')->nullable()->index();$t->string('rule_code',100)->index();$t->boolean('is_enabled')->default(1);$t->string('severity_override',30)->nullable();$t->longText('settings')->nullable();$t->unsignedBigInteger('updated_by')->nullable();$t->timestamps();$t->unique(['business_id','rule_code'],'audit_rule_business_unique');});}public function down(){Schema::dropIfExists('audit_rule_settings');}}
