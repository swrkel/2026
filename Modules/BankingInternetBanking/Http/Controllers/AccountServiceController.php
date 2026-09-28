<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class AccountServiceController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::accounts.index', ['title' => 'Account Services']);
    }
}
