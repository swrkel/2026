<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class CreditRiskController extends Controller
{
    public function index()
    {
        return view('bankingrisk::credit_risk/index', [
            'pageTitle' => 'Credit Risk',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
