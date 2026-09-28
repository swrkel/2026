<?php

namespace Modules\PetroGeneral\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Services\Dashboard\DashboardSummaryService;

class DashboardController extends Controller
{
    protected $summaryService;

    public function __construct(DashboardSummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function index(Request $request)
    {
        /*
         * MA-002 (IS-1909): resolve the business from either session key.
         *
         * This read session('business.id') alone. That key is not always
         * populated - the same gap made the Add User screen build the wrong
         * username suffix - and when it is empty every query below filters on
         * business_id = 0 and returns nothing, so the dashboard renders its
         * cards and tables with no rows and looks broken rather than empty.
         *
         * user.business_id is set for any signed-in business user, so it is the
         * right fallback.
         */
        $businessId = (int) (session('business.id') ?: session('user.business_id'));

        return view('petrogeneral::dashboard.index', [
            'summary' => $this->summaryService->getSummary($businessId),
            'tanks' => $this->summaryService->getTankBalances($businessId),
            'pump_summary' => $this->summaryService->getPumpSummary($businessId),
        ]);
    }
}
