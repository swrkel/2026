<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class CrmReportController extends Controller
{
    public function index()
    {
        return view('bankingcrm::reports/index', [
            'pageTitle' => 'CRM Reports',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
