<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletTransfer;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletTransferService;

class DigitalWalletTransferController extends Controller
{
    public function index()
    {
        $transfers = DigitalWalletTransfer::with(['fromWallet', 'toWallet'])->latest()->paginate(50);
        return view('digitalwallet::transfers.index', compact('transfers'));
    }

    public function create()
    {
        $wallets = DigitalWallet::active()->orderBy('wallet_name')->get();
        return view('digitalwallet::transfers.form', compact('wallets'));
    }

    public function store(Request $request, DigitalWalletTransferService $service)
    {
        $data = $request->validate([
            'from_wallet_id' => 'required|integer|exists:digital_wallets,id',
            'to_wallet_id' => 'required|integer|exists:digital_wallets,id|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.000001',
            'note' => 'nullable|string',
        ]);

        $fromWallet = DigitalWallet::findOrFail($data['from_wallet_id']);
        $toWallet = DigitalWallet::findOrFail($data['to_wallet_id']);
        $service->transfer($fromWallet, $toWallet, (float) $data['amount'], ['note' => $data['note'] ?? null]);

        return redirect()->route('digitalwallet.transfers.index')->with('status', 'Wallet transfer completed successfully.');
    }
}
