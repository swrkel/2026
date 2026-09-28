<?php

namespace Modules\AirlineTicketingNew\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(int $businessId): array
    {
        return [
            'today_bookings' => $this->countIfTableExists('atn_bookings', $businessId, 'booking_date'),
            'today_tickets' => $this->countIfTableExists('atn_tickets', $businessId, 'issued_at'),
            'pending_ticketing' => $this->countByStatus('atn_bookings', $businessId, 'pending_ticketing'),
            'pending_refunds' => $this->countByStatus('atn_refunds', $businessId, 'pending'),
        ];
    }

    private function countIfTableExists(string $table, int $businessId, string $dateColumn): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('business_id', $businessId)
            ->whereDate($dateColumn, now()->toDateString())
            ->count();
    }

    private function countByStatus(string $table, int $businessId, string $status): int
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('business_id', $businessId)
            ->where('status', $status)
            ->count();
    }
}
