<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Database/SQL/01_CREATE/087_to_094_create_tables.sql',
            'Database/SQL/02_ALTER/087_to_094_add_performance_indexes.sql',
        ] as $file) {
            $sql = File::get(module_path('AirlineTicketingNew', $file));
            foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
                DB::unprepared($statement);
            }
        }
    }

    public function down(): void
    {
        foreach (['atn_module_upgrades','atn_health_incidents','atn_queue_executions'] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
