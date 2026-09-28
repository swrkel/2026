<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class LiquidityRiskController extends Controller
{
    public function index()
    {
        return view('bankingrisk::liquidity_risk/index', [
            'pageTitle' => 'Liquidity Risk',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
