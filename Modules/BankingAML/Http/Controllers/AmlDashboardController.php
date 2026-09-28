<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class AmlDashboardController extends Controller
{
    public function index()
    {
        return view('bankingaml::dashboard/index', [
            'pageTitle' => 'AML Dashboard',
            'moduleName' => 'BankingAML',
        ]);
    }
}
