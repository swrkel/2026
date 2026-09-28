<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class CampaignController extends Controller
{
    public function index()
    {
        return view('bankingcrm::campaigns/index', [
            'pageTitle' => 'Campaigns',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
