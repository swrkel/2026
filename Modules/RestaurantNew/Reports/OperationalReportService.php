<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;

class OperationalReportService
{
    public function waiterSales(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_orders as o')
            ->leftJoin('rn_staff_members as s', 's.id', '=', 'o.waiter_id')
            ->where('o.business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('o.location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('o.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('o.created_at', '<=', $v))
            ->selectRaw('COALESCE(s.name, "Unassigned") as staff_name, COUNT(*) as bills, SUM(o.grand_total) as total')
            ->groupBy('s.name')
            ->orderByDesc('total')
            ->get();
    }

    public function tableSales(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_orders as o')
            ->leftJoin('rn_tables as t', 't.id', '=', 'o.restaurant_table_id')
            ->where('o.business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('o.location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('o.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('o.created_at', '<=', $v))
            ->selectRaw('COALESCE(t.name, "No Table") as table_name, COUNT(*) as bills, SUM(o.grand_total) as total')
            ->groupBy('t.name')
            ->orderByDesc('total')
            ->get();
    }

    public function cancelledAndVoid(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_orders')
            ->where('business_id', $businessId)
            ->whereIn('order_status', ['cancelled', 'void'])
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('created_at', '<=', $v))
            ->select(
                'order_no',
                DB::raw('order_status as status'),
                DB::raw('grand_total as total_amount'),
                DB::raw('CASE WHEN order_status = "cancelled" THEN order_note ELSE NULL END as cancel_reason'),
                DB::raw('CASE WHEN order_status = "void" THEN order_note ELSE NULL END as void_reason'),
                'created_at'
            )
            ->orderByDesc('created_at')
            ->get();
    }
}
