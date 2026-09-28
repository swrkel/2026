<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class ComplianceAlertController extends Controller
{
    public function index()
    {
        return view('bankingaml::alerts/index', [
            'pageTitle' => 'Compliance Alerts',
            'moduleName' => 'BankingAML',
        ]);
    }
}
