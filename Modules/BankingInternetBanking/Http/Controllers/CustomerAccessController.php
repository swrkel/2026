<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class CustomerAccessController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::access.index', ['title' => 'Customer Access']);
    }
}
