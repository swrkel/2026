<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class RegulatoryReportController extends Controller
{
    public function index()
    {
        return view('bankingaml::regulatory_reports/index', [
            'pageTitle' => 'Regulatory Reports',
            'moduleName' => 'BankingAML',
        ]);
    }
}
