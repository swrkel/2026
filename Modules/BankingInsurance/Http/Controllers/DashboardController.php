<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Services\SummaryService;

class DashboardController extends Controller
{
    public function index(SummaryService $summary)
    {
        $data = $summary->dashboard($this->businessId(), request('location_id'));
        return view('bankinginsurance::dashboard.index', compact('data'));
    }

    public function data(SummaryService $summary)
    {
        return response()->json($summary->dashboard($this->businessId(), request('location_id')));
    }
}
