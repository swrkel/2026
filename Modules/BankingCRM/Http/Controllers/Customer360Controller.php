<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class Customer360Controller extends Controller
{
    public function index()
    {
        return view('bankingcrm::customers/index', [
            'pageTitle' => 'Customer 360',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
