<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class CustomerInteractionController extends Controller
{
    public function index()
    {
        return view('bankingcrm::interactions/index', [
            'pageTitle' => 'Interactions',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
