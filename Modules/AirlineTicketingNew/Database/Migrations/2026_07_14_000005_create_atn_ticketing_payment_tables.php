<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew', 'Database/SQL/01_CREATE/005_create_atn_ticketing_payment_tables.sql'));

        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_receipts',
            'atn_payment_allocations',
            'atn_payments',
            'atn_invoices',
            'atn_ticket_segments',
            'atn_tickets',
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
