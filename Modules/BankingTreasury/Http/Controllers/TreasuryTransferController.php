<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryRegistryService;

class TreasuryTransferController extends Controller
{
    public function index(TreasuryRegistryService $service)
    {
        return view('bankingtreasury::transfers.index', [
            'title' => 'Inter-Branch Treasury Transfers',
            'records' => $service->items(),
        ]);
    }
}
