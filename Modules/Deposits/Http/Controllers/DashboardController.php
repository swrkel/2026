<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Services\DepositSummaryService;

class DashboardController extends Controller
{
    public function index(DepositSummaryService $summaryService)
    {
        $summary = $summaryService->dashboard();
        $tablesReady = Schema::hasTable('deposit_accounts') && Schema::hasTable('deposit_products');

        return view('deposits::dashboard.index', compact('summary', 'tablesReady'));
    }

    public function health()
    {
        return response()->json(['module' => 'Deposits', 'status' => 'ok']);
    }
}
