<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew', 'Database/SQL/01_CREATE/003_create_atn_profile_tables.sql'));

        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_corporate_contacts',
            'atn_passenger_emergency_contacts',
            'atn_passenger_loyalty_accounts',
            'atn_passenger_visas',
            'atn_passenger_documents',
            'atn_passengers',
            'atn_corporate_customers',
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
