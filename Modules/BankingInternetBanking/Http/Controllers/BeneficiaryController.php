<?php

namespace Modules\BankingInternetBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class BeneficiaryController extends Controller
{
    public function index()
    {
        return view('bankinginternetbanking::beneficiaries.index', ['title' => 'Beneficiary Management']);
    }
}
