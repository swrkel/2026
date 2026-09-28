<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class AmlSettingController extends Controller
{
    public function index()
    {
        return view('bankingaml::settings/index', [
            'pageTitle' => 'AML Settings',
            'moduleName' => 'BankingAML',
        ]);
    }
}
