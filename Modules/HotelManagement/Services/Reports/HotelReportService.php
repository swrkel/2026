<?php
namespace Modules\HotelManagement\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HotelReportService
{
    public function dateRange(array $filters): array
    {
        $from = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : now()->startOfMonth();
        $to = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    protected function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? (Auth::check() ? (Auth::user()->business_id ?? null) : null);
    }

    protected function locationId(): ?int
    {
        return session('business_location_id') ?? session('location_id') ?? request()->input('location_id');
    }

    protected function tableExists(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function scoped(string $table)
    {
        $q = DB::table($table);
        if (Schema::hasColumn($table, 'business_id') && $this->businessId()) { $q->where('business_id', $this->businessId()); }
        if (Schema::hasColumn($table, 'business_location_id') && $this->locationId()) { $q->where('business_location_id', $this->locationId()); }
        if (Schema::hasColumn($table, 'deleted_at')) { $q->whereNull('deleted_at'); }
        return $q;
    }

    public function occupancy(array $filters = []): array
    {
        [$from, $to] = $this->dateRange($filters);
        $days = max(1, $from->diffInDays($to) + 1);
        $totalRooms = $this->tableExists('hm_rooms') ? (int) $this->scoped('hm_rooms')->count() : 0;
        $availableRoomNights = max($totalRooms * $days, 1);
        $occupiedRoomNights = 0;
        if ($this->tableExists('hm_reservations') && Schema::hasColumn('hm_reservations', 'arrival_date')) {
            $occupiedRoomNights = (int) $this->scoped('hm_reservations')
                ->whereIn('status', ['confirmed','checked_in','checked_out'])
                ->whereDate('arrival_date', '<=', $to->toDateString())
                ->whereDate('departure_date', '>=', $from->toDateString())
                ->count();
        } elseif ($this->tableExists('hm_reservation_rooms')) {
            $occupiedRoomNights = (int) $this->scoped('hm_reservation_rooms')->whereBetween('created_at', [$from, $to])->count();
        }
        return [
            'total_rooms' => $totalRooms,
            'occupied_room_nights' => $occupiedRoomNights,
            'available_room_nights' => $availableRoomNights,
            'occupancy_percent' => round(($occupiedRoomNights / $availableRoomNights) * 100, 2),
        ];
    }

    public function revenue(array $filters = []): array
    {
        [$from, $to] = $this->dateRange($filters);
        $roomRevenue = $this->tableExists('hm_folio_lines') ? (float) $this->scoped('hm_folio_lines')->whereBetween('created_at', [$from, $to])->sum('amount') : 0;
        $payments = $this->tableExists('hm_guest_payments') ? (float) $this->scoped('hm_guest_payments')->whereBetween('created_at', [$from, $to])->sum('amount') : 0;
        $roomsSold = $this->tableExists('hm_reservations') ? max((int) $this->scoped('hm_reservations')->whereBetween('created_at', [$from, $to])->whereIn('status', ['checked_in','checked_out'])->count(), 1) : 1;
        $availableRooms = $this->tableExists('hm_rooms') ? max((int) $this->scoped('hm_rooms')->count(), 1) : 1;
        return [
            'room_revenue' => $roomRevenue,
            'payments' => $payments,
            'balance' => round($roomRevenue - $payments, 4),
            'adr' => round($roomRevenue / $roomsSold, 4),
            'revpar' => round($roomRevenue / $availableRooms, 4),
        ];
    }

    public function reservationRegister(array $filters = []) { return $this->rows('hm_reservations', $filters); }
    public function housekeeping(array $filters = []) { return $this->rows('hm_housekeeping_tasks', $filters); }
    public function guestLedger(array $filters = []) { return $this->rows('hm_folios', $filters); }

    public function checkinCheckout(array $filters = [])
    {
        return ['checkins'=>$this->rows('hm_checkins', $filters), 'checkouts'=>$this->rows('hm_checkouts', $filters)];
    }


    public function analytics(array $filters = []): array
    {
        [$from, $to] = $this->dateRange($filters);
        $occupancy = $this->occupancy($filters);
        $revenue = $this->revenue($filters);
        $reservations = $this->tableExists('hm_reservations') ? (int) $this->scoped('hm_reservations')->whereBetween('created_at', [$from, $to])->count() : 0;
        $checkins = $this->tableExists('hm_checkins') ? (int) $this->scoped('hm_checkins')->whereBetween('created_at', [$from, $to])->count() : 0;
        $checkouts = $this->tableExists('hm_checkouts') ? (int) $this->scoped('hm_checkouts')->whereBetween('created_at', [$from, $to])->count() : 0;
        $openFolios = $this->tableExists('hm_folios') ? (int) $this->scoped('hm_folios')->whereIn('status', ['open','checked_in'])->count() : 0;
        $openMaintenance = $this->tableExists('hm_maintenance_work_orders') ? (int) $this->scoped('hm_maintenance_work_orders')->whereNotIn('status', ['completed','cancelled'])->count() : 0;
        $dirtyRooms = $this->tableExists('hm_rooms') ? (int) $this->scoped('hm_rooms')->whereIn('status', ['dirty','cleaning'])->count() : 0;
        $roomService = $this->tableExists('hm_room_service_orders') ? (float) $this->scoped('hm_room_service_orders')->whereBetween('created_at', [$from, $to])->sum('total_amount') : 0;
        $posRevenue = $this->tableExists('hm_pos_orders') ? (float) $this->scoped('hm_pos_orders')->whereBetween('created_at', [$from, $to])->sum('total_amount') : 0;
        $banquetRevenue = $this->tableExists('hm_banquet_events') ? (float) $this->scoped('hm_banquet_events')->whereBetween('created_at', [$from, $to])->sum('total_amount') : 0;
        $conferenceRevenue = $this->tableExists('hm_conference_bookings') ? (float) $this->scoped('hm_conference_bookings')->whereBetween('created_at', [$from, $to])->sum('total_amount') : 0;

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'occupancy' => $occupancy,
            'revenue' => $revenue,
            'counts' => compact('reservations','checkins','checkouts','openFolios','openMaintenance','dirtyRooms'),
            'department_revenue' => [
                'rooms' => round((float)($revenue['room_revenue'] ?? 0), 4),
                'pos' => round($posRevenue, 4),
                'room_service' => round($roomService, 4),
                'banquets' => round($banquetRevenue, 4),
                'conference' => round($conferenceRevenue, 4),
            ],
        ];
    }

    public function saveSnapshot(string $reportKey, array $filters = []): int
    {
        if (!$this->tableExists('hm_report_snapshots')) { return 0; }
        $payload = $this->analytics($filters);
        $id = DB::table('hm_report_snapshots')->insertGetId([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'report_key' => $reportKey ?: 'hotel_analytics',
            'snapshot_date' => now()->toDateString(),
            'payload' => json_encode($payload),
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return (int) $id;
    }

    public function snapshots(array $filters = [])
    {
        if (!$this->tableExists('hm_report_snapshots')) { return collect(); }
        [$from, $to] = $this->dateRange($filters);
        return $this->scoped('hm_report_snapshots')
            ->whereBetween('snapshot_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('snapshot_date')->orderByDesc('id')->limit(100)->get();
    }

    protected function rows(string $table, array $filters)
    {
        if (!$this->tableExists($table)) { return collect(); }
        [$from, $to] = $this->dateRange($filters);
        $q = $this->scoped($table)->whereBetween('created_at', [$from, $to]);
        return $q->orderByDesc('id')->limit(1000)->get();
    }
}
