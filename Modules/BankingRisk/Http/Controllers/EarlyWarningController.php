<?php

namespace Modules\BankingRisk\Http\Controllers;

use Illuminate\Routing\Controller;

class EarlyWarningController extends Controller
{
    public function index()
    {
        return view('bankingrisk::early_warning/index', [
            'pageTitle' => 'Early Warning',
            'moduleName' => 'BankingRisk',
        ]);
    }
}
