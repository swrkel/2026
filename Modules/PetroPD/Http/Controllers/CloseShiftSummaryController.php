<?php

namespace Modules\PetroPD\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\PetroPD\Services\CloseShiftSummaryResponse;

class CloseShiftSummaryController extends Controller
{
    /**
     * Display the most recently completed close-shift report
     * for the currently authenticated session.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $report = $request->session()->get(
            CloseShiftSummaryResponse::SESSION_KEY
        );

        if (! is_array($report) || empty($report)) {
            return redirect()->route('petropd.pd-operators.dashboard')->with(
                'warning',
                'No completed close-shift summary is available.'
            );
        }

        return view(
            'petropd::pumper_dashboard.close_shift_summary',
            compact('report')
        );
    }
}
