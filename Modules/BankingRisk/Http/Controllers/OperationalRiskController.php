<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class OperationalRiskController extends Controller
{
    public function index()
    {
        return view('bankingrisk::operational_risk/index', [
            'pageTitle' => 'Operational Risk',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
