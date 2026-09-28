<?php

namespace Modules\DistributionNew\Http\Controllers\Reports;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Reports\ReturnsReportService;

class ReturnsReportController extends Controller
{
    public function index(Request $request, ReturnsReportService $service)
    {
        $filters = $request->all();
        $filters['business_id'] = session('business.id');
        $summary = $service->summary($filters);
        return view('distributionnew::reports.returns_summary', compact('summary', 'filters'));
    }
}
