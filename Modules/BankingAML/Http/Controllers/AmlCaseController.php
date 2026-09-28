<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class AmlCaseController extends Controller
{
    public function index()
    {
        return view('bankingaml::cases/index', [
            'pageTitle' => 'AML Cases',
            'moduleName' => 'BankingAML',
        ]);
    }
}
