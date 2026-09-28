<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Services\BranchReportService;

class BranchReportController extends Controller
{
    protected BranchReportService $service;

    public function __construct(BranchReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $summary = $this->service->summary($request->all());
        return view('beautysaloons::branch_reports.index', compact('summary'));
    }

    public function branchSales(Request $request)
    {
        $summary = $this->service->summary($request->all());
        return view('beautysaloons::branch_reports.branch_sales', compact('summary'));
    }

    public function resourceUtilization(Request $request)
    {
        $summary = $this->service->summary($request->all());
        return view('beautysaloons::branch_reports.resource_utilization', compact('summary'));
    }
}
