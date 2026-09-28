<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\WalletService;

class WalletController extends Controller
{
    public function index(WalletService $service)
    {
        return view('beautysaloons::wallets.index', $service->indexData());
    }

    public function create()
    {
        return view('beautysaloons::wallets.create');
    }

    public function store(Request $request, WalletService $service)
    {
        $service->createWallet($request->all());
        return redirect()->route('beauty-saloons.wallets.index')->with('status', 'Wallet created successfully');
    }

    public function show($id, WalletService $service)
    {
        return view('beautysaloons::wallets.statement', $service->statementData((int) $id));
    }

    public function topUp(Request $request, $id, WalletService $service)
    {
        $service->topUp((int) $id, $request->all());
        return redirect()->back()->with('status', 'Wallet top-up saved successfully');
    }

    public function debit(Request $request, $id, WalletService $service)
    {
        $service->debit((int) $id, $request->all());
        return redirect()->back()->with('status', 'Wallet debit saved successfully');
    }

    public function refund(Request $request, $id, WalletService $service)
    {
        $service->refund((int) $id, $request->all());
        return redirect()->back()->with('status', 'Wallet refund saved successfully');
    }
}
