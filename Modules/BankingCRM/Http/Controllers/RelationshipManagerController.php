<?php

namespace Modules\BankingCRM\Http\Controllers;

use Illuminate\Routing\Controller;

class RelationshipManagerController extends Controller
{
    public function index()
    {
        return view('bankingcrm::relationships/index', [
            'pageTitle' => 'Relationship Managers',
            'moduleName' => 'BankingCRM',
        ]);
    }
}
