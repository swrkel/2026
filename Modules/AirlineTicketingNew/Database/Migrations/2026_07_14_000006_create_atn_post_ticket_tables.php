<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew', 'Database/SQL/01_CREATE/006_create_atn_post_ticket_tables.sql'));

        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_ticket_action_history',
            'atn_credit_notes',
            'atn_refunds',
            'atn_ticket_cancellations',
            'atn_ticket_voids',
            'atn_ticket_reissues',
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
