<?php

namespace Modules\DistributionNew\Services\Dashboards;

use Illuminate\Support\Facades\DB;

class RoleDashboardService
{
    public function cards(int $businessId, int $locationId = null, string $role = 'manager'): array
    {
        $orders = DB::table('disnew_sales_orders')->where('business_id', $businessId);
        $invoices = DB::table('disnew_sales_invoices')->where('business_id', $businessId);
        $deliveries = DB::table('disnew_deliveries')->where('business_id', $businessId);
        $vehicles = DB::table('disnew_vehicles')->where('business_id', $businessId);

        if ($locationId) {
            foreach ([$orders, $invoices, $deliveries, $vehicles] as $query) {
                $query->where('business_location_id', $locationId);
            }
        }

        return [
            'role' => $role,
            'orders_today' => (clone $orders)->whereDate('created_at', now()->toDateString())->count(),
            'pending_orders' => (clone $orders)->whereIn('status', ['draft','pending','pending_approval'])->count(),
            'invoices_today' => (clone $invoices)->whereDate('created_at', now()->toDateString())->count(),
            'deliveries_pending' => (clone $deliveries)->whereNotIn('status', ['delivered','cancelled'])->count(),
            'vehicles_active' => (clone $vehicles)->where('status', 'active')->count(),
        ];
    }
}
