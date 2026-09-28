<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
return new class extends Migration {
 public function up():void{
  $sql=File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/027_to_031_create_tables.sql'));
  foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $s) DB::unprepared($s);
 }
 public function down():void{
  foreach(['atn_api_credentials','atn_feature_switches','atn_workflow_instances','atn_workflow_definitions','atn_managed_document_versions','atn_managed_documents','atn_flight_disruptions','atn_flight_schedules','atn_gds_request_logs','atn_gds_provider_settings'] as $t) DB::statement('DROP TABLE IF EXISTS `'.$t.'`');
 }
};
