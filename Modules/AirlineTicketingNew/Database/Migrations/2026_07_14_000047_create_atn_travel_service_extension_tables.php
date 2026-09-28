<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $sql = File::get(module_path('AirlineTicketingNew','Database/SQL/01_CREATE/047_to_054_create_tables.sql'));
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql))) as $statement) {
            DB::unprepared($statement);
        }
    }

    public function down(): void
    {
        foreach ([
            'atn_service_bundle_items','atn_service_bundles','atn_supplier_service_agreements',
            'atn_ancillary_bookings','atn_ancillary_services','atn_tour_departures',
            'atn_transfer_routes','atn_travel_insurance_policies','atn_insurance_providers',
            'atn_hotel_availability','atn_hotel_room_types','atn_visa_status_history'
        ] as $table) {
            DB::statement('DROP TABLE IF EXISTS `' . $table . '`');
        }
    }
};
