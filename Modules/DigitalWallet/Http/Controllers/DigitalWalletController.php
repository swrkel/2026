<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Services\Reports\DigitalWalletReportService;

class DigitalWalletController extends Controller
{
    public function dashboard(DigitalWalletReportService $reports)
    {
        return view('digitalwallet::dashboard', ['summary' => $reports->dashboard()]);
    }
}
