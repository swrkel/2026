<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletType;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletHierarchyService;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletService;

class DigitalWalletWalletController extends Controller
{
    public function index()
    {
        $wallets = DigitalWallet::latest()->paginate(50);
        return view('digitalwallet::wallets.index', compact('wallets'));
    }

    public function create(DigitalWalletHierarchyService $hierarchyService)
    {
        return view('digitalwallet::wallets.form', [
            'wallet' => new DigitalWallet(),
            'wallets' => DigitalWallet::orderBy('wallet_name')->get(),
            'types' => DigitalWalletType::where('is_active', true)->orderBy('type_name')->get(),
            'levels' => $hierarchyService->levelOptions(),
        ]);
    }

    public function store(Request $request, DigitalWalletService $walletService)
    {
        $data = $request->validate([
            'wallet_name' => 'required|string|max:191',
            'wallet_type' => 'nullable|string|max:100',
            'owner_type' => 'nullable|string|max:100',
            'owner_id' => 'nullable|integer',
            'parent_wallet_id' => 'nullable|integer',
            'business_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'hierarchy_level' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'available_balance' => 'nullable|numeric|min:0',
            'low_balance_threshold' => 'nullable|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'daily_spend_limit' => 'nullable|numeric|min:0',
            'monthly_spend_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'is_locked' => 'nullable|boolean',
            'locked_reason' => 'nullable|string',
        ]);

        $walletService->createWallet($data);
        return redirect()->route('digitalwallet.wallets.index')->with('status', 'Wallet created successfully.');
    }

    public function edit(DigitalWallet $wallet, DigitalWalletHierarchyService $hierarchyService)
    {
        return view('digitalwallet::wallets.form', [
            'wallet' => $wallet,
            'wallets' => DigitalWallet::where('id', '!=', $wallet->id)->orderBy('wallet_name')->get(),
            'types' => DigitalWalletType::where('is_active', true)->orderBy('type_name')->get(),
            'levels' => $hierarchyService->levelOptions(),
        ]);
    }

    public function update(Request $request, DigitalWallet $wallet)
    {
        $data = $request->validate([
            'wallet_name' => 'required|string|max:191',
            'wallet_type' => 'nullable|string|max:100',
            'owner_type' => 'nullable|string|max:100',
            'owner_id' => 'nullable|integer',
            'parent_wallet_id' => 'nullable|integer',
            'business_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'hierarchy_level' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:10',
            'low_balance_threshold' => 'nullable|numeric|min:0',
            'credit_limit' => 'nullable|numeric|min:0',
            'daily_spend_limit' => 'nullable|numeric|min:0',
            'monthly_spend_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'is_locked' => 'nullable|boolean',
            'locked_reason' => 'nullable|string',
        ]);

        $wallet->update($data);
        return redirect()->route('digitalwallet.wallets.index')->with('status', 'Wallet updated successfully.');
    }

    public function destroy(DigitalWallet $wallet)
    {
        $wallet->delete();
        return redirect()->route('digitalwallet.wallets.index')->with('status', 'Wallet deleted successfully.');
    }
}
