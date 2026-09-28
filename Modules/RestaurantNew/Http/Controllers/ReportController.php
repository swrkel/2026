<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Reports\SalesReportService;
use Modules\RestaurantNew\Reports\ItemSalesReportService;
use Modules\RestaurantNew\Reports\OperationalReportService;
use Modules\RestaurantNew\Reports\PaymentTaxReportService;

class ReportController extends Controller
{
    public function index()
    {
        return view('restaurantnew::reports.index');
    }

    public function dailySummary(Request $request, SalesReportService $service)
    {
        $data = $service->dailySummary($request->all());
        return view('restaurantnew::reports.daily-summary', $data);
    }

    public function itemSales(Request $request, ItemSalesReportService $service)
    {
        return view('restaurantnew::reports.item-sales', ['rows' => $service->itemSales($request->all())]);
    }

    public function categorySales(Request $request, ItemSalesReportService $service)
    {
        return view('restaurantnew::reports.category-sales', ['rows' => $service->categorySales($request->all())]);
    }

    public function waiterSales(Request $request, OperationalReportService $service)
    {
        return view('restaurantnew::reports.waiter-sales', ['rows' => $service->waiterSales($request->all())]);
    }

    public function tableSales(Request $request, OperationalReportService $service)
    {
        return view('restaurantnew::reports.table-sales', ['rows' => $service->tableSales($request->all())]);
    }

    public function cancelledVoid(Request $request, OperationalReportService $service)
    {
        return view('restaurantnew::reports.cancelled-void', ['rows' => $service->cancelledAndVoid($request->all())]);
    }

    public function payments(Request $request, PaymentTaxReportService $service)
    {
        return view('restaurantnew::reports.payments', ['rows' => $service->payments($request->all())]);
    }

    public function taxService(Request $request, PaymentTaxReportService $service)
    {
        return view('restaurantnew::reports.tax-service', ['rows' => $service->taxAndServiceCharge($request->all())]);
    }
}
