<?php

namespace Modules\Reporting\Http\Controllers\Financial;

use App\Account;
use App\AccountType;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BalanceSheetController extends Controller
{
    /**
     * Branch Balance Sheet
     */
    public function branch(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $selected_location_id = $request->get('location_id');

        $as_at_date = $request->get('as_at_date');

        $asset_type_ids = $this->getAccountTypeIds($business_id, 'Assets');
        $liability_type_ids = $this->getAccountTypeIds($business_id, 'Liabilities');
        $equity_type_ids = $this->getAccountTypeIds($business_id, 'Equity');

        $asset_accounts = collect();
        $liability_accounts = collect();
        $equity_accounts = collect();

        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;

        if (!empty($selected_location_id)) {

            /**
             * Assets
             */
            $asset_accounts = Account::where('business_id', $business_id)
                ->where('location_id', $selected_location_id)
                ->whereIn('account_type_id', $asset_type_ids)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->get();

            foreach ($asset_accounts as $account) {

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

                $total_assets += $balance;
            }

            /**
             * Liabilities
             */
            $liability_accounts = Account::where('business_id', $business_id)
                ->where('location_id', $selected_location_id)
                ->whereIn('account_type_id', $liability_type_ids)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->get();

            foreach ($liability_accounts as $account) {

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

                $total_liabilities += abs($balance);
            }

            /**
             * Equity
             */
            $equity_accounts = Account::where('business_id', $business_id)
                ->where('location_id', $selected_location_id)
                ->whereIn('account_type_id', $equity_type_ids)
                ->where('is_closed', 0)
                ->orderBy('name')
                ->get();

            foreach ($equity_accounts as $account) {

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

                $total_equity += abs($balance);
            }
        }

        return view(
            'reporting::financial.balance_sheet.branch'
        )->with(compact(
            'locations',
            'selected_location_id',
            'as_at_date',
            'asset_accounts',
            'liability_accounts',
            'equity_accounts',
            'total_assets',
            'total_liabilities',
            'total_equity'
        ));
    }

    /**
     * Consolidated Balance Sheet
     */
    public function consolidated(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $as_at_date = $request->get('as_at_date');

        $asset_type_ids = $this->getAccountTypeIds($business_id, 'Assets');
        $liability_type_ids = $this->getAccountTypeIds($business_id, 'Liabilities');
        $equity_type_ids = $this->getAccountTypeIds($business_id, 'Equity');

        /**
         * Assets
         */
        $asset_accounts = Account::where('business_id', $business_id)
            ->whereIn('account_type_id', $asset_type_ids)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        /**
         * Liabilities
         */
        $liability_accounts = Account::where('business_id', $business_id)
            ->whereIn('account_type_id', $liability_type_ids)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        /**
         * Equity
         */
        $equity_accounts = Account::where('business_id', $business_id)
            ->whereIn('account_type_id', $equity_type_ids)
            ->where('is_closed', 0)
            ->orderBy('location_id')
            ->orderBy('name')
            ->get();

        $total_assets = 0;
        $total_liabilities = 0;
        $total_equity = 0;

        foreach ($asset_accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                null,
                $as_at_date
            );

            $account->balance = round($balance, 2);

            $total_assets += $balance;
        }

        foreach ($liability_accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                null,
                $as_at_date
            );

            $account->balance = round($balance, 2);

            $total_liabilities += abs($balance);
        }

        foreach ($equity_accounts as $account) {

            $balance = Account::getAccountBalance(
                $account->id,
                null,
                $as_at_date
            );

            $account->balance = round($balance, 2);

            $total_equity += abs($balance);
        }

        return view(
            'reporting::financial.balance_sheet.consolidated'
        )->with(compact(
            'as_at_date',
            'asset_accounts',
            'liability_accounts',
            'equity_accounts',
            'total_assets',
            'total_liabilities',
            'total_equity'
        ));
    }

    /**
     * Get parent and child account type ids
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