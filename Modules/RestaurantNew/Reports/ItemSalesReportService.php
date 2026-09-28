<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;

class ItemSalesReportService
{
    public function itemSales(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_order_lines as l')
            ->join('restaurant_new_orders as o', 'o.id', '=', 'l.order_id')
            ->leftJoin('rn_menu_items as mi', 'mi.id', '=', 'l.menu_item_id')
            ->where('o.business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('o.location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('o.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('o.created_at', '<=', $v))
            ->selectRaw('l.menu_item_id, COALESCE(mi.name, l.item_name) as item_name, SUM(l.qty) as qty, SUM(l.line_total) as total')
            ->groupBy('l.menu_item_id', 'mi.name', 'l.item_name')
            ->orderByDesc('total')
            ->get();
    }

    public function categorySales(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_order_lines as l')
            ->join('restaurant_new_orders as o', 'o.id', '=', 'l.order_id')
            ->leftJoin('rn_menu_items as mi', 'mi.id', '=', 'l.menu_item_id')
            ->leftJoin('rn_menu_categories as c', 'c.id', '=', 'mi.menu_category_id')
            ->where('o.business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('o.location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('o.created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('o.created_at', '<=', $v))
            ->selectRaw('COALESCE(c.name, "Uncategorised") as category_name, SUM(l.qty) as qty, SUM(l.line_total) as total')
            ->groupBy('c.name')
            ->orderByDesc('total')
            ->get();
    }
}
