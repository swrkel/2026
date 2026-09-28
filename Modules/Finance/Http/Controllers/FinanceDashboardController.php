<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountType;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceEscalation;
use Modules\Finance\Entities\FinanceNotification;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

class FinanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->pluck('name', 'location_id');

        $location_id = $request->get(
            'location_id',
            'all'
        );

        /*
        |--------------------------------------------------------------------------
        | Core Accounting Totals
        |--------------------------------------------------------------------------
        */

        $asset_total = $this->getTotalByType(
            $business_id,
            'Assets',
            $location_id
        );

        $liability_total = $this->getTotalByType(
            $business_id,
            'Liabilities',
            $location_id
        );

        $equity_total = $this->getTotalByType(
            $business_id,
            'Equity',
            $location_id
        );

        $income_total = $this->getTotalByType(
            $business_id,
            'Income',
            $location_id
        );

        $expense_total = $this->getTotalByType(
            $business_id,
            'Expenses',
            $location_id
        );

        $net_profit =
            $income_total - $expense_total;

        $balance_difference =
            $asset_total -
            ($liability_total + $equity_total);

        /*
        |--------------------------------------------------------------------------
        | Treasury
        |--------------------------------------------------------------------------
        */

        $treasury_cash_in =
            FinanceTreasuryTransaction::where(
                'business_id',
                $business_id
            )
            ->whereIn('treasury_type', [
                'cash_in',
                'bank_deposit'
            ])
            ->sum('amount');

        $treasury_cash_out =
            FinanceTreasuryTransaction::where(
                'business_id',
                $business_id
            )
            ->whereIn('treasury_type', [
                'cash_out',
                'bank_withdrawal'
            ])
            ->sum('amount');

        $treasury_net_position =
            $treasury_cash_in -
            $treasury_cash_out;

        /*
        |--------------------------------------------------------------------------
        | Escalations
        |--------------------------------------------------------------------------
        */

        $open_escalations =
            FinanceEscalation::where(
                'business_id',
                $business_id
            )
            ->whereIn('status', [
                'open',
                'in_progress'
            ])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        $unread_notifications =
            FinanceNotification::where(
                'business_id',
                $business_id
            )
            ->where('status', 'unread')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Existing System Modules
        |--------------------------------------------------------------------------
        */

        $total_sales = 0;
        $total_purchases = 0;
        $total_expenses = 0;
        $customer_receipts = 0;
        $supplier_payments = 0;

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        */

        if (
            DB::getSchemaBuilder()
                ->hasTable('transactions')
        ) {

            $sales_query =
                DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'sell');

            if (
                !empty($location_id)
                && $location_id != 'all'
                && DB::getSchemaBuilder()
                    ->hasColumn(
                        'transactions',
                        'location_id'
                    )
            ) {
                $sales_query->where(
                    'location_id',
                    $location_id
                );
            }

            $total_sales =
                $sales_query->sum('final_total');

            /*
            |--------------------------------------------------------------------------
            | Purchases
            |--------------------------------------------------------------------------
            */

            $purchase_query =
                DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'purchase');

            if (
                !empty($location_id)
                && $location_id != 'all'
                && DB::getSchemaBuilder()
                    ->hasColumn(
                        'transactions',
                        'location_id'
                    )
            ) {
                $purchase_query->where(
                    'location_id',
                    $location_id
                );
            }

            $total_purchases =
                $purchase_query->sum('final_total');
        }

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        if (
            DB::getSchemaBuilder()
                ->hasTable('expense_transactions')
        ) {

            $expense_query =
                DB::table('expense_transactions')
                ->where('business_id', $business_id);

            if (
                !empty($location_id)
                && $location_id != 'all'
                && DB::getSchemaBuilder()
                    ->hasColumn(
                        'expense_transactions',
                        'location_id'
                    )
            ) {
                $expense_query->where(
                    'location_id',
                    $location_id
                );
            }

            $total_expenses =
                $expense_query->sum('amount');
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Receipts
        |--------------------------------------------------------------------------
        */

        if (
            DB::getSchemaBuilder()
                ->hasTable('transaction_payments')
        ) {

            $customer_query =
                DB::table('transaction_payments')
                ->join(
                    'transactions',
                    'transaction_payments.transaction_id',
                    '=',
                    'transactions.id'
                )
                ->where(
                    'transactions.business_id',
                    $business_id
                )
                ->where(
                    'transactions.type',
                    'sell'
                );

            if (
                !empty($location_id)
                && $location_id != 'all'
                && DB::getSchemaBuilder()
                    ->hasColumn(
                        'transactions',
                        'location_id'
                    )
            ) {
                $customer_query->where(
                    'transactions.location_id',
                    $location_id
                );
            }

            $customer_receipts =
                $customer_query->sum(
                    'transaction_payments.amount'
                );

            /*
            |--------------------------------------------------------------------------
            | Supplier Payments
            |--------------------------------------------------------------------------
            */

            $supplier_query =
                DB::table('transaction_payments')
                ->join(
                    'transactions',
                    'transaction_payments.transaction_id',
                    '=',
                    'transactions.id'
                )
                ->where(
                    'transactions.business_id',
                    $business_id
                )
                ->where(
                    'transactions.type',
                    'purchase'
                );

            if (
                !empty($location_id)
                && $location_id != 'all'
                && DB::getSchemaBuilder()
                    ->hasColumn(
                        'transactions',
                        'location_id'
                    )
            ) {
                $supplier_query->where(
                    'transactions.location_id',
                    $location_id
                );
            }

            $supplier_payments =
                $supplier_query->sum(
                    'transaction_payments.amount'
                );
        }
/*
|--------------------------------------------------------------------------
| Executive KPI Metrics
|--------------------------------------------------------------------------
*/

$total_receivables = 0;
$total_payables = 0;

if (
    DB::getSchemaBuilder()
        ->hasTable('transactions')
) {

    /*
    |--------------------------------------------------------------------------
    | Customer Receivables
    |--------------------------------------------------------------------------
    */

    $receivable_query =
        DB::table('transactions')
        ->where('business_id', $business_id)
        ->where('type', 'sell');

    if (
        !empty($location_id)
        && $location_id != 'all'
        && DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'location_id'
            )
    ) {
        $receivable_query->where(
            'location_id',
            $location_id
        );
    }

    if (
        DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'final_total'
            )
        &&
        DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'payment_status'
            )
    ) {

        $total_receivables =
            $receivable_query
            ->whereIn('payment_status', [
                'due',
                'partial'
            ])
            ->sum('final_total');
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier Payables
    |--------------------------------------------------------------------------
    */

    $payable_query =
        DB::table('transactions')
        ->where('business_id', $business_id)
        ->where('type', 'purchase');

    if (
        !empty($location_id)
        && $location_id != 'all'
        && DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'location_id'
            )
    ) {
        $payable_query->where(
            'location_id',
            $location_id
        );
    }

    if (
        DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'final_total'
            )
        &&
        DB::getSchemaBuilder()
            ->hasColumn(
                'transactions',
                'payment_status'
            )
    ) {

        $total_payables =
            $payable_query
            ->whereIn('payment_status', [
                'due',
                'partial'
            ])
            ->sum('final_total');
    }
}

        return view('finance::dashboard.index')
            ->with(compact(
                'locations',
                'location_id',

                'asset_total',
                'liability_total',
                'equity_total',
                'income_total',
                'expense_total',
                'net_profit',
                'balance_difference',

                'treasury_cash_in',
                'treasury_cash_out',
                'treasury_net_position',

                'open_escalations',
                'unread_notifications',

                'total_sales',
                'total_purchases',
                'total_expenses',
                'customer_receipts',
                'supplier_payments',
                'total_receivables',
'total_payables',
            ));
    }

    protected function getTotalByType(
        $business_id,
        $type_name,
        $location_id = 'all'
    ) {
        $type_ids =
            $this->getAccountTypeIds(
                $business_id,
                $type_name
            );

        if (empty($type_ids)) {
            return 0;
        }

        $query = Account::where(
            'business_id',
            $business_id
        )
        ->whereIn(
            'account_type_id',
            $type_ids
        )
        ->where('is_closed', 0);

        if (
            !empty($location_id)
            && $location_id != 'all'
        ) {
            $query->where(
                'location_id',
                $location_id
            );
        }

        $accounts = $query->get();

        $total = 0;

        foreach ($accounts as $account) {

            $total +=
                Account::getAccountBalance(
                    $account->id,
                    null,
                    null,
                    false,
                    false,
                    false,
                    $location_id
                );
        }

        return round($total, 2);
    }

    protected function getAccountTypeIds(
        $business_id,
        $type_name
    ) {
        $parent_type =
            AccountType::where(
                'business_id',
                $business_id
            )
            ->where(
                'name',
                $type_name
            )
            ->first();

        if (empty($parent_type)) {
            return [];
        }

        $type_ids = [
            $parent_type->id
        ];

        $child_type_ids =
            AccountType::where(
                'business_id',
                $business_id
            )
            ->where(
                'parent_account_type_id',
                $parent_type->id
            )
            ->pluck('id')
            ->toArray();

        return array_merge(
            $type_ids,
            $child_type_ids
        );
    }
}