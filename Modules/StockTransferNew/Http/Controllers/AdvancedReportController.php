<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\StockTransferAdvancedReportService;
use Modules\StockTransferNew\Services\StockTransferExportService;

class AdvancedReportController extends Controller
{
    public function __construct(
        protected StockTransferAdvancedReportService $reports,
        protected StockTransferExportService $exports
    ) {}

    public function index()
    {
        return view('stocktransfernew::reports.advanced.index');
    }

    public function productWise(Request $request)
    {
        $rows = $this->reports->productWise($request->all());
        $title = __('stocktransfernew::lang.product_wise_transfer_analysis');
        return view('stocktransfernew::reports.advanced.product_wise', compact('rows', 'title'));
    }

    public function locationWise(Request $request)
    {
        $rows = $this->reports->locationWise($request->all());
        $title = __('stocktransfernew::lang.location_wise_transfer_analysis');
        return view('stocktransfernew::reports.advanced.location_wise', compact('rows', 'title'));
    }

    public function storeWise(Request $request)
    {
        $rows = $this->reports->storeWise($request->all());
        $title = __('stocktransfernew::lang.store_wise_transfer_analysis');
        return view('stocktransfernew::reports.advanced.store_wise', compact('rows', 'title'));
    }

    public function vehicleWise(Request $request)
    {
        $rows = $this->reports->vehicleWise($request->all());
        $title = __('stocktransfernew::lang.vehicle_wise_transfer_analysis');
        return view('stocktransfernew::reports.advanced.vehicle_wise', compact('rows', 'title'));
    }

    public function userWise(Request $request)
    {
        $rows = $this->reports->userWise($request->all());
        $title = __('stocktransfernew::lang.user_wise_transfer_analysis');
        return view('stocktransfernew::reports.advanced.user_wise', compact('rows', 'title'));
    }

    public function monthlyTrend(Request $request)
    {
        $rows = $this->reports->monthlyTrend($request->all());
        $title = __('stocktransfernew::lang.monthly_transfer_trend');
        return view('stocktransfernew::reports.advanced.monthly_trend', compact('rows', 'title'));
    }

    public function exceptions(Request $request)
    {
        $rows = $this->reports->exceptionSummary($request->all());
        $title = __('stocktransfernew::lang.transfer_exception_report');
        return view('stocktransfernew::reports.advanced.exceptions', compact('rows', 'title'));
    }

    public function export(Request $request, string $type)
    {
        $safeType = preg_replace('/[^a-z0-9\-]/i', '', $type);
        return $this->exports->csv(
            'stock-transfer-new-'.$safeType.'-'.date('YmdHis').'.csv',
            [],
            $this->reports->exportRows($safeType, $request->all())
        );
    }
}
