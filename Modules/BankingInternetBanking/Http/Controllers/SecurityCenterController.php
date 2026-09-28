<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class SecurityCenterController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::security.index', ['title' => 'Security Centre']);
    }
}
