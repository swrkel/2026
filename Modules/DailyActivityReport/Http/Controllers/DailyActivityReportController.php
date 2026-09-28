<?php
namespace Modules\DailyActivityReport\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DailyActivityReportController extends Controller
{
    public function index(Request $request)
    {
        Log::info('DailyActivityReportController@index called');

        $business_id = $request->session()->get('user.business_id');
        $from_date   = $request->from_date ?? Carbon::today()->format('Y-m-d');
        $to_date     = $request->to_date ?? Carbon::today()->format('Y-m-d');

        $business = Business::find($business_id);

        $pumps = \DB::table('pumps')
            ->where('business_id', $business_id)
            ->get();

        // Fetch other sales
        $otherSales = \DB::table('other_sales')
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$from_date . ' 00:00:00', $to_date . ' 23:59:59'])
            ->get();

        // Customer payments
        $customerPayments = \DB::table('customer_payments')
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$from_date . ' 00:00:00', $to_date . ' 23:59:59'])
            ->get();

        $user = User::where('business_id', $business_id)->first();

        // Purchases
        $purchases = \DB::table('customer_purchases')
            ->where('user_id', $user->id)
            ->whereBetween('sold_at', [$from_date . ' 00:00:00', $to_date . ' 23:59:59'])
            ->get();

        $salesSummary = \DB::table('customer_payments')
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$from_date . ' 00:00:00', $to_date . ' 23:59:59'])
            ->selectRaw('
        SUM(amount) as total,
        SUM(CASE WHEN payment_method="cash" THEN amount ELSE 0 END) as cash,
        SUM(CASE WHEN payment_method="credit" THEN amount ELSE 0 END) as credit,
        SUM(CASE WHEN payment_method="card" THEN amount ELSE 0 END) as card,
        SUM(CASE WHEN payment_method="cheque" THEN amount ELSE 0 END) as cheques
    ')
            ->first();

        // Payments
        $payments = \DB::table('customer_payments')
            ->where('business_id', operator: $business_id)
            ->whereBetween('created_at', [$from_date, $to_date])
            ->get();

        // Credit sales
        $creditSales = \DB::table('customer_purchases')
            ->where('user_id', $user->id)
        // ->where('payment_status', 'credit')
            ->whereBetween('created_at', [$from_date, $to_date])
            ->get();

        // Expenses
        $expenses = \DB::table('vat_expenses')
            ->where('business_id', $business_id)
            ->whereBetween('created_at', [$from_date, $to_date])
            ->get();

        // Accounts balances
        $accounts = Account::where('business_id', $business_id)->get()->filter(function ($account) use ($from_date, $to_date) {
            $transactions = AccountTransaction::where('account_id', $account->id)
                ->whereBetween('operation_date', [$from_date, $to_date]);

            if ($transactions->exists()) {
                $account->balance = $transactions->sum('amount');
                return true;
            }
            return false;
        });

        $from_date = $request->from_date ?? Carbon::today()->format('Y-m-d');
        $to_date   = $request->to_date ?? Carbon::today()->format('Y-m-d');

        $tanks = \DB::table('fuel_tanks as ft')
            ->leftJoin('tank_purchase_lines as tpl', function ($join) use ($from_date, $to_date) {
                $join->on('tpl.tank_id', '=', 'ft.id')
                    ->whereBetween('tpl.created_at', [$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
            })
            ->where('ft.business_id', $business_id)
            ->select(
                'ft.fuel_tank_number as number',
                \DB::raw('COALESCE(SUM(tpl.instock_qty),0) as opening'),
                \DB::raw('COALESCE(SUM(tpl.quantity),0) as purchase'),
                \DB::raw('0 as testing'),
                'ft.current_balance as balance',
                \DB::raw('(COALESCE(SUM(tpl.instock_qty),0) + COALESCE(SUM(tpl.quantity),0) - ft.current_balance) as sold')
            )
            ->groupBy('ft.id', 'ft.fuel_tank_number', 'ft.current_balance')
            ->get();

        // Opening and closing stock
        $opening_stock = $this->getOpeningStock($business_id, $from_date);
        $closing_stock = $this->getClosingStock($business_id, $to_date);

        $locations = \DB::table('business_locations')
            // ->where('business_id', $business_id)
            ->get();

        $selected_location_id = $request->get('location_id') ?? ($locations->first()->id ?? null);

        return view('dailyactivityreport::index')->with(compact(
            'business',
            'locations',
            'selected_location_id',
            'from_date',
            'to_date',
            'pumps',
            'otherSales',
            'customerPayments',
            'purchases',
            'salesSummary',
            'payments',
            'creditSales',
            'expenses',
            'accounts',
            'tanks',
            'opening_stock',
            'closing_stock'
        ));
    }

    protected function getOpeningStock($business_id, $date)
    {
        return AccountTransaction::where('business_id', $business_id)
            ->whereDate('operation_date', '<', $date)
            ->sum('amount'); // Replace with your stock calculation logic
    }

    protected function getClosingStock($business_id, $date)
    {
        return AccountTransaction::where('business_id', $business_id)
            ->whereDate('operation_date', '<=', $date)
            ->sum('amount'); // Replace with your stock calculation logic
    }
}
