<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/055_to_062_create_tables.sql'));
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_operational_exceptions','atn_approval_matrices','atn_travel_policies',
            'atn_electronic_misc_documents','atn_agency_credit_memos','atn_agency_debit_memos',
            'atn_refund_rules','atn_ticket_exchanges','atn_reissue_quotes'
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
