<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Services\Reports\DigitalWalletReportService;

class DigitalWalletLedgerController extends Controller
{
    public function index(Request $request, DigitalWalletReportService $reports)
    {
        $ledger = $reports->ledger($request->all());
        return view('digitalwallet::ledger.index', compact('ledger'));
    }
}
