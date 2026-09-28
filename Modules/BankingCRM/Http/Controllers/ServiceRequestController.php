<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class ServiceRequestController extends Controller
{
    public function index()
    {
        return view('bankingcrm::service_requests/index', [
            'pageTitle' => 'Service Requests',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
