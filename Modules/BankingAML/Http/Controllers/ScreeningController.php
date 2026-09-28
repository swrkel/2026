<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class ScreeningController extends Controller
{
    public function index()
    {
        return view('bankingaml::screening/index', [
            'pageTitle' => 'Screening',
            'moduleName' => 'BankingAML',
        ]);
    }
}
