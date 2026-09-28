<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class ComplianceAuditController extends Controller
{
    public function index()
    {
        return view('bankingaml::audit/index', [
            'pageTitle' => 'Compliance Audit',
            'moduleName' => 'BankingAML',
        ]);
    }
}
