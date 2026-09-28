<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $indexes = [
            'hm_reservation_rooms' => [
                'hm_rr_business_reservation_idx' => ['business_id', 'reservation_id'],
                'hm_rr_business_room_idx' => ['business_id', 'room_id'],
            ],
            'hm_checkins' => [
                'hm_checkins_business_status_idx' => ['business_id', 'status'],
                'hm_checkins_business_room_idx' => ['business_id', 'room_id'],
            ],
            'hm_checkouts' => [
                'hm_checkouts_business_room_idx' => ['business_id', 'room_id'],
            ],
            'hm_folios' => [
                'hm_folios_business_status_idx' => ['business_id', 'status'],
                'hm_folios_business_reservation_idx' => ['business_id', 'reservation_id'],
            ],
            'hm_folio_lines' => [
                'hm_folio_lines_folio_date_idx' => ['folio_id', 'charge_date'],
            ],
            'hm_guest_payments' => [
                'hm_guest_payments_folio_date_idx' => ['folio_id', 'payment_date'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($tableIndexes) {
                foreach ($tableIndexes as $name => $columns) {
                    $blueprint->index($columns, $name);
                }
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'hm_reservation_rooms' => ['hm_rr_business_reservation_idx','hm_rr_business_room_idx'],
            'hm_checkins' => ['hm_checkins_business_status_idx','hm_checkins_business_room_idx'],
            'hm_checkouts' => ['hm_checkouts_business_room_idx'],
            'hm_folios' => ['hm_folios_business_status_idx','hm_folios_business_reservation_idx'],
            'hm_folio_lines' => ['hm_folio_lines_folio_date_idx'],
            'hm_guest_payments' => ['hm_guest_payments_folio_date_idx'],
        ];
        foreach ($indexes as $table => $names) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($names) {
                foreach ($names as $name) { $blueprint->dropIndex($name); }
            });
        }
    }
};
