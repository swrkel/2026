<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
return new class extends Migration {
 public function up():void{
  $sql=File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/022_to_026_create_tables.sql'));
  foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $s) DB::unprepared($s);
 }
 public function down():void{
  foreach(['atn_loyalty_transactions','atn_crm_interactions','atn_bsp_adjustments','atn_bsp_periods','atn_currency_revaluations','atn_exchange_rates','atn_journal_lines','atn_journal_entries','atn_account_mappings'] as $t) DB::statement('DROP TABLE IF EXISTS `'.$t.'`');
 }
};
