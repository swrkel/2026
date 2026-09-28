<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Services\Reports\DigitalWalletReportService;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletService;

class DigitalWalletTransactionController extends Controller
{
    public function index(Request $request, DigitalWalletReportService $reports)
    {
        $transactions = $reports->transactions($request->all());
        return view('digitalwallet::transactions.index', compact('transactions'));
    }

    public function topup(Request $request, DigitalWallet $wallet, DigitalWalletService $walletService)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.000001', 'note' => 'nullable|string|max:500']);
        $walletService->topup($wallet, (float) $data['amount'], ['note' => $data['note'] ?? null, 'source_module' => 'DigitalWallet']);
        return back()->with('status', 'Wallet top-up recorded.');
    }

    public function charge(Request $request, DigitalWallet $wallet, DigitalWalletService $walletService)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.000001', 'note' => 'nullable|string|max:500']);
        $walletService->charge($wallet, (float) $data['amount'], ['note' => $data['note'] ?? null, 'source_module' => 'DigitalWallet']);
        return back()->with('status', 'Wallet charge recorded.');
    }
}
