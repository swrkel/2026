<?php

namespace Modules\Finance\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Services\Reports\TrialBalanceRewriteService;

/**
 * Finance account reports controller.
 *
 * MA-002: NO LONGER A BRIDGE TO CORE.
 *
 * This class previously declared
 *     extends App\Http\Controllers\AccountReportsController
 * which pulled 1,602 lines of core controller code into the Finance module.
 *
 * Nothing inherited was ever used. The class defines only two methods, both
 * its own, and only those two are routed:
 *
 *     index()                  a redirect to finance.trial_balance
 *     trialBalanceRewriteData() uses Finance's own TrialBalanceRewriteService
 *
 * Verified before changing: Modules/Finance/Routes/account_reports.php maps
 * exactly these two method names and nothing else, so no inherited method was
 * reachable through any route.
 *
 * It now extends Illuminate\Routing\Controller, the same base the other
 * non-bridge Finance controllers use. Behaviour is unchanged; the module
 * simply no longer depends on that core class.
 *
 * Finance bridge controllers remaining: 19 -> 18.
 */
class AccountReportsController extends Controller
{
    /**
     * Stable Finance report landing page.
     */
    public function index()
    {
        return redirect()->route('finance.trial_balance');
    }

    public function trialBalanceRewriteData(Request $request, TrialBalanceRewriteService $service)
    {
        $business_id = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));

        $rows = $service->rows(
            $business_id,
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('location_id'),
            $request->input('srch')
        );

        return response()->json([
            'data' => $rows,
            'total_debit' => round($rows->sum('debit_raw'), 2),
            'total_credit' => round($rows->sum('credit_raw'), 2),
        ]);
    }
}
