<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Services\Reports\DigitalWalletReportService;

class DigitalWalletReportController extends Controller
{
    public function index(Request $request, DigitalWalletReportService $reports)
    {
        return view('digitalwallet::reports.index', [
            'summary' => $reports->dashboard(),
            'transactions' => $reports->transactions($request->all()),
        ]);
    }

    public function export(Request $request, DigitalWalletReportService $reports)
    {
        $transactions = $reports->transactions($request->all());
        return view('digitalwallet::reports.export', compact('transactions'));
    }
}
