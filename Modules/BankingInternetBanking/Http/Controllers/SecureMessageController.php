<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class SecureMessageController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::messages.index', ['title' => 'Secure Messaging']);
    }
}
