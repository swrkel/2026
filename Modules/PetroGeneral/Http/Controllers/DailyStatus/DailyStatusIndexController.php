<?php

namespace Modules\PetroGeneral\Http\Controllers\DailyStatus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Services\DailyStatus\DailyStatusSummaryService;

class DailyStatusIndexController extends Controller
{
    protected $dailyStatusSummaryService;

    public function __construct(DailyStatusSummaryService $dailyStatusSummaryService)
    {
        $this->dailyStatusSummaryService = $dailyStatusSummaryService;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('business.id');

        return view('petrogeneral::daily_status.index', [
            'active_tab' => $request->get('tab', 'summary'),
            'summary' => $this->dailyStatusSummaryService->getSummary($businessId, $request),
            'sales' => $this->dailyStatusSummaryService->getSales($businessId, $request),
            'payments' => $this->dailyStatusSummaryService->getPayments($businessId, $request),
            'stock' => $this->dailyStatusSummaryService->getStock($businessId, $request),
        ]);
    }
}
