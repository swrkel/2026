<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\StockTransferReportService;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class DashboardController extends Controller
{
    protected StockTransferReportService $reports;

    public function __construct(StockTransferReportService $reports)
    {
        $this->reports = $reports;
    }

    public function index()
    {
        $stats = $this->reports->summary(
            (int) StockTransferTenant::businessId()
        );

        return view('stocktransfernew::dashboard.index', compact('stats'));
    }
}
