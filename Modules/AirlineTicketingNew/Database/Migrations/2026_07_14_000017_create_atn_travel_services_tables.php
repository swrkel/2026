<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/017_to_021_create_tables.sql'));
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_transfer_bookings','atn_transport_vehicles','atn_hotel_reservations','atn_hotels',
            'atn_tour_bookings','atn_tour_packages','atn_visa_checklist_items','atn_visa_applications',
            'atn_corporate_ledgers','atn_corporate_agreements'
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
