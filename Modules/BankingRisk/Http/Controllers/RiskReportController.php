<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class RiskReportController extends Controller
{
    public function index()
    {
        return view('bankingrisk::reports/index', [
            'pageTitle' => 'Risk Reports',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
