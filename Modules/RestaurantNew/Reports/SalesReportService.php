<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;

class SalesReportService
{
    public function filters(array $filters): array
    {
        return [
            'business_id' => $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id'),
            'location_id' => $filters['location_id'] ?? null,
            'date_from' => $filters['date_from'] ?? now()->startOfDay()->toDateTimeString(),
            'date_to' => $filters['date_to'] ?? now()->endOfDay()->toDateTimeString(),
            'order_type' => $filters['order_type'] ?? null,
            'staff_id' => $filters['staff_id'] ?? null,
            'table_id' => $filters['table_id'] ?? null,
        ];
    }

    protected function base(array $filters)
    {
        $f = $this->filters($filters);
        return DB::table('restaurant_new_orders as o')
            ->where('o.business_id', $f['business_id'])
            ->whereBetween('o.created_at', [$f['date_from'], $f['date_to']])
            ->when($f['location_id'], fn($q) => $q->where('o.location_id', $f['location_id']))
            ->when($f['order_type'], fn($q) => $q->where('o.order_type', $f['order_type']))
            ->when($f['staff_id'], fn($q) => $q->where('o.waiter_id', $f['staff_id']))
            ->when($f['table_id'], fn($q) => $q->where('o.restaurant_table_id', $f['table_id']));
    }

    public function dailySummary(array $filters): array
    {
        $rows = $this->base($filters)
            ->selectRaw('DATE(o.created_at) as sale_date, COUNT(*) as bills, SUM(o.subtotal) as subtotal, SUM(o.discount_amount) as discount, SUM(o.tax_amount) as tax, SUM(o.service_charge_amount) as service_charge, SUM(o.grand_total) as total')
            ->groupBy(DB::raw('DATE(o.created_at)'))
            ->orderBy('sale_date', 'desc')
            ->get();

        return ['rows' => $rows, 'totals' => $this->totals($rows)];
    }

    public function byOrderType(array $filters)
    {
        return $this->base($filters)
            ->selectRaw('o.order_type, COUNT(*) as bills, SUM(o.grand_total) as total')
            ->groupBy('o.order_type')
            ->orderByDesc('total')
            ->get();
    }

    public function totals($rows): array
    {
        return [
            'bills' => $rows->sum('bills'),
            'subtotal' => $rows->sum('subtotal'),
            'discount' => $rows->sum('discount'),
            'tax' => $rows->sum('tax'),
            'service_charge' => $rows->sum('service_charge'),
            'total' => $rows->sum('total'),
        ];
    }
}
