<?php

namespace Modules\Finance\Http\Controllers\Financial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Services\Accounts\FinanceAccountBalanceService;

class TrialBalanceController extends Controller
{
    public function __construct(private FinanceAccountBalanceService $balanceService)
    {
    }

    public function index(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));

        $locations = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');

        $locationId = $request->query('location_id');
        if ($locationId === null || $locationId === '') {
            $locationId = $request->session()->get('business.location_id')
                ?: $request->session()->get('user.location_id')
                ?: 'all';
        }
        $locationFilter = $locationId === 'all' || (int) $locationId <= 0 ? null : (int) $locationId;

        $fromDate = $request->query('from_date') ?: $request->query('start_date');
        $toDate = $request->query('to_date') ?: $request->query('end_date');

        $accounts = $this->balanceService->getTrialBalanceAccounts(
            $businessId,
            $fromDate,
            $toDate,
            $locationFilter
        );

        $totalDebit = round((float) $accounts->sum('debit'), 4);
        $totalCredit = round((float) $accounts->sum('credit'), 4);

        return view('finance::financial.trial_balance.index')->with([
            'accounts' => $accounts,
            'locations' => $locations,
            'location_id' => $locationId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ]);
    }
}
