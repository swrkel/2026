<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class StressTestController extends Controller
{
    public function index()
    {
        return view('bankingrisk::stress_tests/index', [
            'pageTitle' => 'Stress Tests',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
