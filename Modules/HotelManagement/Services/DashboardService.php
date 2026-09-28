<?php

namespace Modules\HotelManagement\Services;

class DashboardService
{
    public function summary(): array
    {
        return [
            'today_arrivals' => 0,
            'today_departures' => 0,
            'occupied_rooms' => 0,
            'available_rooms' => 0,
            'dirty_rooms' => 0,
            'maintenance_rooms' => 0,
            'today_revenue' => 0,
            'pending_folios' => 0,
        ];
    }
}
