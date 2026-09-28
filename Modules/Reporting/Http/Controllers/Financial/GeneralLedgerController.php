<?php

namespace Modules\Reporting\Http\Controllers\Financial;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('reporting::financial.general_ledger.index')
            ->with(compact(
                'locations',
                'accounts'
            ));
    }

    public function accountLedger(Request $request, $account_id)
    {
        $business_id = session()->get('user.business_id');

        $location_id = $request->get('location_id');
        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');

        $account = Account::where('business_id', $business_id)
            ->where('id', $account_id)
            ->firstOrFail();

        $query = AccountTransaction::where('account_transactions.business_id', $business_id)
            ->where('account_transactions.account_id', $account_id)
            ->whereNull('account_transactions.deleted_at');

        if (!empty($from_date)) {
            $query->whereDate('account_transactions.operation_date', '>=', $from_date);
        }

        if (!empty($to_date)) {
            $query->whereDate('account_transactions.operation_date', '<=', $to_date);
        }

        if (!empty($location_id) && $location_id != 'all') {
            $query->leftJoin(
                'transactions',
                'account_transactions.transaction_id',
                '=',
                'transactions.id'
            );

            $query->where('transactions.location_id', $location_id);
        }

        $transactions = $query
            ->select('account_transactions.*')
            ->orderBy('account_transactions.operation_date', 'asc')
            ->orderBy('account_transactions.id', 'asc')
            ->get();

        $running_balance = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->type == 'debit') {
                $running_balance += $transaction->amount;
            } else {
                $running_balance -= $transaction->amount;
            }

            $transaction->running_balance = round($running_balance, 2);
        }

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        return view('reporting::financial.general_ledger.account')
            ->with(compact(
                'account',
                'transactions',
                'locations',
                'location_id',
                'from_date',
                'to_date',
                'running_balance'
            ));
    }
}