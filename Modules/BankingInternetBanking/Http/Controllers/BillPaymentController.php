<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class BillPaymentController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::bills.index', ['title' => 'Bill Payments']);
    }
}
