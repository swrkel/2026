<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\Pawning\Services\PawningSummaryService;

class DashboardController extends Controller
{
    public function index(PawningSummaryService $summaryService)
    {
        $summary = $summaryService->dashboard();
        $tablesReady = Schema::hasTable('pawning_pledges') && Schema::hasTable('pawning_products');

        return view('pawning::dashboard.index', compact('summary', 'tablesReady'));
    }
}
