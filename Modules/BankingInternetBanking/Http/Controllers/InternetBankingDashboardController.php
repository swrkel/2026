<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingInternetBanking\Services\InternetBankingDashboardService;

class InternetBankingDashboardController extends Controller
{
    public function index(InternetBankingDashboardService $service)
    {
        return view('bankinginternetbanking::dashboard.index', [
            'title' => 'Internet Banking',
            'summary' => $service->summary(),
        ]);
    }
}
