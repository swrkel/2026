<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class ComplaintController extends Controller
{
    public function index()
    {
        return view('bankingcrm::complaints/index', [
            'pageTitle' => 'Complaints',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
