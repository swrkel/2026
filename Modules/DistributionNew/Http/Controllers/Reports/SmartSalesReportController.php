<?php

namespace Modules\DistributionNew\Http\Controllers\Reports;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewSalesKpiSnapshot;
use Modules\DistributionNew\Models\DisnewSalesTarget;
use Modules\DistributionNew\Models\DisnewSalesCommission;

class SmartSalesReportController extends Controller
{
    public function kpi(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $rows = DisnewSalesKpiSnapshot::where('business_id', $businessId)->latest('snapshot_date')->paginate(50);
        return view('distributionnew::reports.smart_sales.kpi', compact('rows'));
    }
    public function salesRepTargets(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $rows = DisnewSalesTarget::where('business_id', $businessId)->latest('period_start')->paginate(50);
        return view('distributionnew::reports.smart_sales.sales_rep_targets', compact('rows'));
    }
    public function commissions(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $rows = DisnewSalesCommission::where('business_id', $businessId)->latest('commission_date')->paginate(50);
        return view('distributionnew::reports.smart_sales.commissions', compact('rows'));
    }
    public function customerProfitability(Request $request)
    {
        return view('distributionnew::reports.smart_sales.customer_profitability');
    }
    public function productProfitability(Request $request)
    {
        return view('distributionnew::reports.smart_sales.product_profitability');
    }
}
