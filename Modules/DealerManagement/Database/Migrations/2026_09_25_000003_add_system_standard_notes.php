<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach (['dlr_dealers','dlr_outlets','dlr_roles','dlr_users','dlr_reorder_rules'] as $table) {
   if (Schema::hasTable($table) && !Schema::hasColumn($table,'notes')) Schema::table($table,function(Blueprint $t){$t->text('notes')->nullable();});
  }
 }
 public function down(): void {
  foreach (['dlr_dealers','dlr_outlets','dlr_roles','dlr_users','dlr_reorder_rules'] as $table) {
   if (Schema::hasTable($table) && Schema::hasColumn($table,'notes')) Schema::table($table,function(Blueprint $t){$t->dropColumn('notes');});
  }
 }
};
