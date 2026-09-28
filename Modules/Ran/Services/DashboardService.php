<?php

namespace Modules\Ran\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ran\Support\RanContext;

class DashboardService
{
    public function __construct(private SetupService $setup) {}

    public function summary(): array
    {
        $this->setup->ensureBusinessDefaults();
        $businessId = RanContext::businessId();
        $today = now()->toDateString();
        return [
            'stockLots' => (int) DB::table('ran_stock_lots')->where('business_id', $businessId)->whereNull('deleted_at')->where('stock_status', 'available')->count(),
            'stockWeight' => (float) DB::table('ran_stock_lots')->where('business_id', $businessId)->whereNull('deleted_at')->where('stock_status', 'available')->sum('net_weight'),
            'stockValue' => (float) DB::table('ran_stock_lots')->where('business_id', $businessId)->whereNull('deleted_at')->where('stock_status', 'available')->sum('total_cost'),
            'openProduction' => (int) DB::table('ran_production_orders')->where('business_id', $businessId)->whereNull('deleted_at')->whereNotIn('status', ['completed','cancelled'])->count(),
            'todaySales' => (float) DB::table('ran_sales')->where('business_id', $businessId)->whereNull('deleted_at')->whereDate('invoice_date', $today)->whereIn('status', ['posted','part_paid','paid'])->sum('total_amount'),
            'customerDue' => (float) DB::table('ran_sales')->where('business_id', $businessId)->whereNull('deleted_at')->whereIn('status', ['posted','part_paid'])->sum('balance_amount'),
            'recentSales' => DB::table('ran_sales')->where('business_id', $businessId)->whereNull('deleted_at')->latest('invoice_date')->latest('id')->limit(7)->get(),
            'recentProduction' => DB::table('ran_production_orders')->where('business_id', $businessId)->whereNull('deleted_at')->latest('order_date')->latest('id')->limit(7)->get(),
        ];
    }
}
