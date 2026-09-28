<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryDashboardService;

class TreasuryDashboardController extends Controller
{
    public function index(TreasuryDashboardService $service)
    {
        return view('bankingtreasury::dashboard.index', [
            'title' => 'Treasury Dashboard',
            'summary' => $service->summary(),
        ]);
    }
}
