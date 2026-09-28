<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Services\LoanAnalyticsReportService;

class LoanAnalyticsReportController extends Controller
{
    protected $service;

    public function __construct(LoanAnalyticsReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date', 'location_id', 'loan_product_id', 'loan_officer_id']);
        $filterData = $this->service->filters();
        $reportData = $this->service->dashboard($filters);

        return view('loan::reports.analytics.index', array_merge($filterData, $reportData, ['filters' => $filters]));
    }
}
