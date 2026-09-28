<?php

namespace Modules\Reporting\Http\Controllers\Financial;

use App\Account;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TrialBalanceController extends Controller
{
    /**
     * Branch Trial Balance
     */
    public function branch(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $selected_location_id = $request->get('location_id');

        $as_at_date = $request->get('as_at_date');

        $accounts = collect();

        $total_debit = 0;
        $total_credit = 0;

        if (!empty($selected_location_id)) {

            $accounts = Account::where('business_id', $business_id)
                ->where(function ($query) use ($selected_location_id) {

                    $query->where('location_id', $selected_location_id)
                        ->orWhereNull('location_id')
                        ->orWhere('location_id', 'all');
                })
                ->where('is_closed', 0)
                ->orderBy('account_type_id')
                ->orderBy('name')
                ->get();

            foreach ($accounts as $account) {

                $balance = Account::getAccountBalance(
                    $account->id,
                    null,
                    $as_at_date,
                    false,
                    false,
                    false,
                    $selected_location_id
                );

                $account->balance = round($balance, 2);

                if ($balance >= 0) {
                    $account->debit = round($balance, 2);
                    $account->credit = 0;

                    $total_debit += $balance;
                } else {
                    $account->debit = 0;
                    $account->credit = round(abs($balance), 2);

                    $total_credit += abs($balance);
                }
            }
        }

        $difference = round($total_debit - $total_credit, 2);

        return view(
            'reporting::financial.trial_balance.branch'
        )->with(compact(
            'locations',
            'selected_location_id',
            'accounts',
            'as_at_date',
            'total_debit',
            'total_credit',
            'difference'
        ));
    }

    /**
     * Consolidated Trial Balance
     */
    public function consolidated(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $as_at_date = $request->get('as_at_date');

        $accounts = Account::where('business_id', $business_id)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('account_type_id')
            ->orderBy('name')
            ->get();

        $total_debit = 0;
        $total_credit = 0;

        foreach ($accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                null,
                $as_at_date
            );

            $account->balance = round($balance, 2);

            if ($balance >= 0) {

                $account->debit = round($balance, 2);
                $account->credit = 0;

                $total_debit += $balance;

            } else {

                $account->debit = 0;
                $account->credit = round(abs($balance), 2);

                $total_credit += abs($balance);
            }
        }

        $difference = round($total_debit - $total_credit, 2);

        return view(
            'reporting::financial.trial_balance.consolidated'
        )->with(compact(
            'accounts',
            'as_at_date',
            'total_debit',
            'total_credit',
            'difference'
        ));
    }
}