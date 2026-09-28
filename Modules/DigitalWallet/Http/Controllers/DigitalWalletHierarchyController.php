<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Services\Wallets\DigitalWalletHierarchyService;

class DigitalWalletHierarchyController extends Controller
{
    public function index(DigitalWalletHierarchyService $service)
    {
        $summary = $service->summary();
        $walletTree = $service->tree();
        return view('digitalwallet::hierarchy.index', compact('summary', 'walletTree'));
    }
}
