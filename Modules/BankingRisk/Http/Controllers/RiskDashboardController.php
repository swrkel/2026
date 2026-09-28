<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class RiskDashboardController extends Controller
{
    public function index()
    {
        return view('bankingrisk::dashboard/index', [
            'pageTitle' => 'Risk Dashboard',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
