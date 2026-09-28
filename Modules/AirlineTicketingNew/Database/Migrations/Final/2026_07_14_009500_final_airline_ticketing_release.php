<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
return new class extends Migration {
 public function up(): void {$sql=File::get(module_path('AirlineTicketingNew','Database/SQL/05_VIEWS/095_create_reporting_views.sql'));foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $s)DB::unprepared($s);}
 public function down(): void {DB::statement('DROP VIEW IF EXISTS `atn_v_ticket_financial_summary`');}
};
