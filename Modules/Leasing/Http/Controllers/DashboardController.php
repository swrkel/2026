<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Leasing\Services\LeasingSummaryService;

class DashboardController extends Controller
{
    public function index(LeasingSummaryService $summaryService)
    {
        $summary = $summaryService->dashboard();
        $tablesReady = Schema::hasTable('leasing_lease_contracts') && Schema::hasTable('leasing_products');

        return view('leasing::dashboard.index', compact('summary', 'tablesReady'));
    }
}
