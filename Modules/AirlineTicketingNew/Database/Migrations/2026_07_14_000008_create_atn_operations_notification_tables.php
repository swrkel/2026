<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/008_create_operations_notification_tables.sql'));
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach (['atn_notification_logs','atn_notification_rules','atn_operational_tasks'] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
