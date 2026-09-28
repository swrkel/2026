<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class DashboardController extends Controller
{
    public function index()
    {
        $businessId = DisnewTenantUtil::businessId();
        $cards = [
            'orders_today' => DB::table('disnew_sales_orders')->where('business_id', $businessId)->whereDate('order_date', now()->toDateString())->count(),
            'pending_orders' => DB::table('disnew_sales_orders')->where('business_id', $businessId)->whereIn('status', ['draft', 'confirmed'])->count(),
            'invoices_today' => DB::table('disnew_sales_invoices')->where('business_id', $businessId)->whereDate('invoice_date', now()->toDateString())->count(),
            'vehicles_loaded' => DB::table('disnew_loadings')->where('business_id', $businessId)->where('status', 'completed')->whereDate('completed_at', now()->toDateString())->count(),
        ];
        return view('distributionnew::dashboard.index', compact('cards'));
    }
}
