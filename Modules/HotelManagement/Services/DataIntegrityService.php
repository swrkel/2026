<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataIntegrityService
{
    protected array $checks = [
        'Reservations without business scope' => ['table' => 'hm_reservations', 'where' => 'business_id IS NULL OR business_location_id IS NULL'],
        'Rooms without business scope' => ['table' => 'hm_rooms', 'where' => 'business_id IS NULL OR business_location_id IS NULL'],
        'Folios without reservation link' => ['table' => 'hm_folios', 'where' => 'reservation_id IS NULL'],
        'Open folios with negative balance' => ['table' => 'hm_folios', 'where' => 'status <> "closed" AND balance < 0'],
        'Room charges without folio' => ['table' => 'hm_folio_lines', 'where' => 'folio_id IS NULL'],
        'Guest payments without folio' => ['table' => 'hm_guest_payments', 'where' => 'folio_id IS NULL'],
        'POS charge-to-room without folio' => ['table' => 'hm_pos_orders', 'where' => 'payment_method = "room_charge" AND folio_id IS NULL'],
        'Room service charge-to-room without folio' => ['table' => 'hm_room_service_orders', 'where' => 'payment_method = "room_charge" AND folio_id IS NULL'],
        'Housekeeping schedules without room' => ['table' => 'hm_housekeeping_schedules', 'where' => 'room_id IS NULL'],
        'Maintenance orders without room/location' => ['table' => 'hm_maintenance_work_orders', 'where' => 'room_id IS NULL AND business_location_id IS NULL'],
    ];

    public function dashboard(): array
    {
        $rows = [];
        foreach ($this->checks as $name => $check) {
            $count = null;
            $status = 'missing';
            $note = 'Required table is missing';

            if (Schema::hasTable($check['table'])) {
                try {
                    $count = DB::table($check['table'])->whereRaw($check['where'])->count();
                    $status = $count == 0 ? 'ok' : 'review';
                    $note = $count == 0 ? 'No inconsistent records found' : $count.' record(s) need review';
                } catch (Throwable $e) {
                    $status = 'review';
                    $note = 'Check could not be completed: '.$e->getMessage();
                }
            }

            $rows[] = [
                'name' => $name,
                'table' => $check['table'],
                'status' => $status,
                'count' => $count,
                'note' => $note,
            ];
        }

        return [
            'summary' => [
                'total' => count($rows),
                'ok' => collect($rows)->where('status', 'ok')->count(),
                'review' => collect($rows)->where('status', 'review')->count(),
                'missing' => collect($rows)->where('status', 'missing')->count(),
            ],
            'rows' => $rows,
            'notes' => [
                'Use this page before client testing and after every large upload to detect cross-scope or orphan records.',
                'This page does not delete or modify records automatically. It only identifies records that need controlled correction.',
                'Run Docs/HOTELMGT_020_SQL.sql only if earlier parcels are already applied. Use the master SQL only for fresh tenants or missed parcels.',
            ],
        ];
    }

    public function saveSnapshot(?int $userId = null): void
    {
        if (!Schema::hasTable('hm_data_integrity_snapshots')) {
            return;
        }

        $dashboard = $this->dashboard();
        DB::table('hm_data_integrity_snapshots')->insert([
            'business_id' => session('business.id') ?? null,
            'business_location_id' => session('business_location_id') ?? session('business.default_location_id') ?? null,
            'snapshot_date' => now()->toDateString(),
            'checks_total' => $dashboard['summary']['total'],
            'checks_ok' => $dashboard['summary']['ok'],
            'checks_review' => $dashboard['summary']['review'],
            'checks_missing' => $dashboard['summary']['missing'],
            'payload' => json_encode($dashboard),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
