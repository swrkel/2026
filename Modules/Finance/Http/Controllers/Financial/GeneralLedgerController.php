<?php

namespace Modules\Finance\Http\Controllers\Financial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\BusinessLocation;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));

        $locations = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');

        $accounts = Account::query()
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('finance::financial.general_ledger.index')->with(compact('locations', 'accounts'));
    }

    /**
     * Keep old General Ledger account URLs working, but render through the
     * paginated Finance Reports Account Ledger instead of loading all history.
     */
    public function accountLedger(Request $request, $accountId)
    {
        $parameters = $request->query();
        $parameters['accountId'] = (int) $accountId;

        return redirect()->route('finance.reports.account_ledger.account', $parameters);
    }
}
