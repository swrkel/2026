<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingInternetBanking\Services\InternetBankingReportService;

class InternetBankingReportController extends Controller
{
    public function index(InternetBankingReportService $service)
    {
        return view('bankinginternetbanking::reports.index', [
            'title' => 'Internet Banking Reports',
            'reports' => $service->reports(),
        ]);
    }
}
