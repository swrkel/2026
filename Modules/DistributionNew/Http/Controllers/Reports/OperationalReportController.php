<?php
namespace Modules\DistributionNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\Reports\DisnewOperationalReportService;
class OperationalReportController extends Controller
{
    public function daily(Request $request, DisnewOperationalReportService $service)
    {
        $summary = $service->dailySummary((int) session('business.id'), $request->all());
        return view('distributionnew::reports.operational.daily', compact('summary'));
    }
    public function vehicleStock(Request $request, DisnewOperationalReportService $service)
    {
        $rows = $service->vehicleStockBalance((int) session('business.id'), $request->all());
        return view('distributionnew::reports.operational.vehicle_stock', compact('rows'));
    }
}
