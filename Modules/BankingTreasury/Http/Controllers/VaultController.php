<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryRegistryService;

class VaultController extends Controller
{
    public function index(TreasuryRegistryService $service)
    {
        return view('bankingtreasury::vaults.index', [
            'title' => 'Vault Management',
            'records' => $service->items(),
        ]);
    }
}
