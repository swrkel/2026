<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class CrmSettingController extends Controller
{
    public function index()
    {
        return view('bankingcrm::settings/index', [
            'pageTitle' => 'CRM Settings',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
