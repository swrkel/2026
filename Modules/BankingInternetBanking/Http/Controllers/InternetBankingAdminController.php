<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class InternetBankingAdminController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::admin.index', ['title' => 'Internet Banking Admin']);
    }
}
