<?php

namespace Modules\BankingAML\Http\Controllers;

use Illuminate\Routing\Controller;

class KycReviewController extends Controller
{
    public function index()
    {
        return view('bankingaml::kyc_reviews/index', [
            'pageTitle' => 'KYC Reviews',
            'moduleName' => 'BankingAML',
        ]);
    }
}
