<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryRegistryService;

class TreasuryReportController extends Controller
{
    public function index(TreasuryRegistryService $service)
    {
        return view('bankingtreasury::reports.index', [
            'title' => 'Treasury Reports',
            'reports' => $service->reports(),
        ]);
    }
}
