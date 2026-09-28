<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class CrmDashboardController extends Controller
{
    public function index()
    {
        return view('bankingcrm::dashboard/index', [
            'pageTitle' => 'CRM Dashboard',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
