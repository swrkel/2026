<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryRegistryService;

class TreasuryDealController extends Controller
{
    public function index(TreasuryRegistryService $service)
    {
        return view('bankingtreasury::deals.index', [
            'title' => 'Treasury Deals',
            'records' => $service->items(),
        ]);
    }
}
