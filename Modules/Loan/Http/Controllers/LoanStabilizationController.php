<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Services\LoanStabilizationSummaryService;

class LoanStabilizationController extends Controller
{
    protected LoanStabilizationSummaryService $summaryService;

    public function __construct(LoanStabilizationSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function index(Request $request)
    {
        $businessId = session('business.id');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;

        $summary = $this->summaryService->dashboardSummary($businessId, $locationId);
        $recentLoans = $this->summaryService->recentOperationalItems(15, $businessId, $locationId);
        $aging = $this->summaryService->arrearsAging($businessId, $locationId);

        return view('loan::stabilization.index', compact('summary', 'recentLoans', 'aging', 'locationId'));
    }

    public function arrearsAging(Request $request)
    {
        $businessId = session('business.id');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;
        $aging = $this->summaryService->arrearsAging($businessId, $locationId);

        return view('loan::stabilization.arrears_aging', compact('aging', 'locationId'));
    }
}
