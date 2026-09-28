<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class RiskSettingController extends Controller
{
    public function index()
    {
        return view('bankingrisk::settings/index', [
            'pageTitle' => 'Risk Settings',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
