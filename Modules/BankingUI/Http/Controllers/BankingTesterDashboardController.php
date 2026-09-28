<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Services\BankingNavigationService;
use Modules\BankingUI\Services\BankingRouteHealthService;

class BankingTesterDashboardController extends Controller
{
    public function index(BankingNavigationService $navigation, BankingRouteHealthService $health)
    {
        return view('bankingui::testing.dashboard', [
            'groups' => $navigation->groupsForUser(auth()->user()),
            'summary' => $navigation->statusSummary(),
            'health' => $health->summary(),
        ]);
    }

    public function checklist(BankingNavigationService $navigation)
    {
        return view('bankingui::testing.checklist', [
            'groups' => $navigation->groupsForUser(auth()->user()),
            'checkpoints' => $navigation->checkpoints(),
        ]);
    }
}
