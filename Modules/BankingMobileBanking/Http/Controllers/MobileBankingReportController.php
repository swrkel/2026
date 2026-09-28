<?php

namespace Modules\BankingMobileBanking\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingMobileBanking\Services\MobileBankingReportService;

class MobileBankingReportController extends Controller
{
    public function index(MobileBankingReportService $reports)
    {
        return view('bankingmobile::reports.index', ['reports' => $reports->registry()]);
    }
}
