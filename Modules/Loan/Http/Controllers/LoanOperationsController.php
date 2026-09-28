<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Services\LoanInstallmentScheduleService;
use Modules\Loan\Services\LoanOperationalSummaryService;

class LoanOperationsController extends Controller
{
    protected LoanOperationalSummaryService $summaryService;
    protected LoanInstallmentScheduleService $scheduleService;

    public function __construct(LoanOperationalSummaryService $summaryService, LoanInstallmentScheduleService $scheduleService)
    {
        $this->summaryService = $summaryService;
        $this->scheduleService = $scheduleService;
    }

    public function index(Request $request)
    {
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;
        $summary = $this->summaryService->summary(null, $locationId);
        $recentLoans = $this->summaryService->recentLoans(10, null, $locationId);

        return view('loan::operations.index', compact('summary', 'recentLoans'));
    }

    public function schedulePreview(Request $request)
    {
        $data = $request->validate([
            'principal' => 'required|numeric|min:0',
            'annual_rate' => 'nullable|numeric|min:0',
            'term' => 'required|integer|min:1|max:600',
            'frequency' => 'nullable|string',
            'start_date' => 'nullable|date',
        ]);

        return response()->json([
            'data' => $this->scheduleService->generate(
                (float) $data['principal'],
                (float) ($data['annual_rate'] ?? 0),
                (int) $data['term'],
                $data['frequency'] ?? 'monthly',
                $data['start_date'] ?? null
            ),
        ]);
    }
}
