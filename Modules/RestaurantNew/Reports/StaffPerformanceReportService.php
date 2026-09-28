<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;

class StaffPerformanceReportService
{
    public function waiterSales(array $filters = [])
    {
        return DB::table('rn_staff_members as s')
            ->leftJoin('rn_sale_orders as o', 'o.waiter_id', '=', 's.id')
            ->select('s.id', 's.name', 's.role', DB::raw('COUNT(o.id) as order_count'), DB::raw('COALESCE(SUM(o.total_amount),0) as total_sales'))
            ->when($filters['business_id'] ?? null, fn ($query, $businessId) => $query->where('s.business_id', $businessId))
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('s.location_id', $locationId))
            ->groupBy('s.id', 's.name', 's.role')
            ->orderByDesc('total_sales')
            ->get();
    }

    public function cashierShiftSummary(array $filters = [])
    {
        return DB::table('rn_cashier_shifts')
            ->when($filters['business_id'] ?? null, fn ($query, $businessId) => $query->where('business_id', $businessId))
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
            ->select('shift_no', 'status', 'opening_cash', 'cash_sales', 'cash_in', 'cash_out', 'expected_cash', 'counted_cash', 'shortage_excess', 'opened_at', 'closed_at')
            ->orderByDesc('opened_at')
            ->get();
    }
}
