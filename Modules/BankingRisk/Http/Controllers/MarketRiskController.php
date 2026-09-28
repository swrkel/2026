<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class MarketRiskController extends Controller
{
    public function index()
    {
        return view('bankingrisk::market_risk/index', [
            'pageTitle' => 'Market Risk',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
