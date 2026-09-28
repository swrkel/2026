<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class TransferController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::transfers.index', ['title' => 'Transfers']);
    }
}
