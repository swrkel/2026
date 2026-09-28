<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql=File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/079_to_086_create_tables.sql'));
        foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $statement){
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach(['atn_enterprise_settings','atn_api_rate_limits','atn_encrypted_settings','atn_security_audit_logs','atn_permission_profiles'] as $table){
            DB::statement('DROP TABLE IF EXISTS `'.$table.'`');
        }
    }
};
