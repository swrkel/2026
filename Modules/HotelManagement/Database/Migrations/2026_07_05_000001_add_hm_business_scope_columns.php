<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'hm_buildings','hm_wings','hm_floors','hm_amenities','hm_room_types','hm_rooms','hm_room_features','hm_rate_plans','hm_seasons',
        'hm_guests','hm_reservations','hm_reservation_rooms','hm_checkins','hm_checkouts','hm_deposits',
        'hm_folio_lines','hm_store_movements','hm_guest_preferences','hm_guest_notes'
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'business_id')) {
                    $blueprint->unsignedBigInteger('business_id')->nullable()->index()->after('id');
                }
                if (!Schema::hasColumn($table, 'business_location_id')) {
                    $blueprint->unsignedBigInteger('business_location_id')->nullable()->index()->after('business_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'business_location_id')) { $blueprint->dropColumn('business_location_id'); }
                if (Schema::hasColumn($table, 'business_id')) { $blueprint->dropColumn('business_id'); }
            });
        }
    }
};
