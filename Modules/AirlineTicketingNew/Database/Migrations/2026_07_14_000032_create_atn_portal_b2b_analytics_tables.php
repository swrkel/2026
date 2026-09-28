<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
return new class extends Migration {
 public function up():void{
  $sql=File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/032_to_036_create_tables.sql'));
  foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $s) DB::unprepared($s);
 }
 public function down():void{
  foreach(['atn_analytics_snapshots','atn_b2b_wallet_transactions','atn_b2b_agents','atn_portal_requests','atn_portal_users'] as $t) DB::statement('DROP TABLE IF EXISTS `'.$t.'`');
 }
};
