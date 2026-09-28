<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $path = module_path('AirlineTicketingNew', 'Database/SQL/01_CREATE/002_create_atn_core_master_tables.sql');
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', File::get($path)))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_commission_rules','atn_tax_rules','atn_currencies','atn_agents','atn_suppliers',
            'atn_routes','atn_travel_classes','atn_aircraft_types','atn_airports','atn_airlines'
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
