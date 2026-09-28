<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Services\BankingNavigationService;

class BankingDashboardController extends Controller
{
    public function index(BankingNavigationService $navigation)
    {
        return view('banking-ui::dashboard.index', [
            'cards' => $navigation->dashboardCards(),
            'menu' => $navigation->menuTree(),
        ]);
    }
}
