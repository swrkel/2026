<?php

namespace Modules\RestaurantNew\Services;

class DashboardService
{
    public function summary(): array
    {
        return [
            'open_orders' => 0,
            'occupied_tables' => 0,
            'today_sales' => 0,
            'pending_kot' => 0,
        ];
    }
}
