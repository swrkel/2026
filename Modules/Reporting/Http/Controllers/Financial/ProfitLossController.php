<?php

namespace Modules\Reporting\Http\Controllers\Financial;

use App\Account;
use App\AccountType;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfitLossController extends Controller
{
    /**
     * Existing compatibility report
     */
    public function index(Request $request)
    {
        return $this->branch($request);
    }

    /**
     * Branch Wise Profit & Loss
     */
    public function branch(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $selected_location_id = $request->get('location_id');

        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');

        $income_type_ids = $this->getAccountTypeIds($business_id, 'Income');
        $expense_type_ids = $this->getAccountTypeIds($business_id, 'Expenses');

        $income_accounts = collect();
        $expense_accounts = collect();

        $total_income = 0;
        $total_expense = 0;

        if (!empty($selected_location_id)) {

            $income_accounts = Account::where('business_id', $business_id)
                ->where('location_id', $selected_location_id)
                ->whereIn('account_type_id', $income_type_ids)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->get();

            foreach ($income_accounts as $account) {

                $balance = Account::getAccountBalance(
                    $account->id,
                    $from_date,
                    $to_date,
                    false,
                    false,
                    false,
                    $selected_location_id
                );

                $account->balance = round($balance, 2);

                $total_income += $balance;
            }

            $expense_accounts = Account::where('business_id', $business_id)
                ->where('location_id', $selected_location_id)
                ->whereIn('account_type_id', $expense_type_ids)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->get();

            foreach ($expense_accounts as $account) {

                $balance = Account::getAccountBalance(
                    $account->id,
                    $from_date,
                    $to_date,
                    false,
                    false,
                    false,
                    $selected_location_id
                );

                $account->balance = round($balance, 2);

                $total_expense += $balance;
            }
        }

        $net_profit = round($total_income - $total_expense, 2);

        return view(
            'reporting::financial.profit_loss.branch'
        )->with(compact(
            'locations',
            'selected_location_id',
            'income_accounts',
            'expense_accounts',
            'from_date',
            'to_date',
            'total_income',
            'total_expense',
            'net_profit'
        ));
    }

    /**
     * Consolidated Profit & Loss
     */
    public function consolidated(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $from_date = $request->get('from_date');
        $to_date = $request->get('to_date');

        $income_type_ids = $this->getAccountTypeIds($business_id, 'Income');
        $expense_type_ids = $this->getAccountTypeIds($business_id, 'Expenses');

        $income_accounts = Account::where('business_id', $business_id)
            ->whereIn('account_type_id', $income_type_ids)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        $expense_accounts = Account::where('business_id', $business_id)
            ->whereIn('account_type_id', $expense_type_ids)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        $total_income = 0;
        $total_expense = 0;

        foreach ($income_accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                $from_date,
                $to_date
            );

            $account->balance = round($balance, 2);

            $total_income += $balance;
        }

        foreach ($expense_accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                $from_date,
                $to_date
            );

            $account->balance = round($balance, 2);

            $total_expense += $balance;
        }

        $net_profit = round($total_income - $total_expense, 2);

        return view(
            'reporting::financial.profit_loss.consolidated'
        )->with(compact(
            'income_accounts',
            'expense_accounts',
            'from_date',
            'to_date',
            'total_income',
            'total_expense',
            'net_profit'
        ));
    }

    /**
     * Get parent and child account type ids by name.
     */
    protected function getAccountTypeIds($business_id, $type_name)
    {
        $parent_type = AccountType::where('business_id', $business_id)
            ->where('name', $type_name)
            ->first();

        if (empty($parent_type)) {
            return [];
        }

        $type_ids = [$parent_type->id];

        $child_type_ids = AccountType::where('business_id', $business_id)
            ->where('parent_account_type_id', $parent_type->id)
            ->pluck('id')
            ->toArray();

        return array_merge($type_ids, $child_type_ids);
    }
}