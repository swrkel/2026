<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\FinanceCashFlowForecast;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

class FinanceCashFlowAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session('business.id');

        $from_date = $request->from_date ?? date('Y-m-01');
        $to_date = $request->to_date ?? date('Y-m-t');

        $projected_inflows = FinanceCashFlowForecast::where('business_id', $business_id)
            ->where('forecast_type', 'inflow')
            ->whereBetween('expected_date', [$from_date, $to_date])
            ->sum('expected_amount');

        $projected_outflows = FinanceCashFlowForecast::where('business_id', $business_id)
            ->where('forecast_type', 'outflow')
            ->whereBetween('expected_date', [$from_date, $to_date])
            ->sum('expected_amount');

        $actual_cash_in = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_in', 'bank_deposit'])
            ->whereBetween('transaction_date', [$from_date, $to_date])
            ->sum('amount');

        $actual_cash_out = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_out', 'bank_withdrawal'])
            ->whereBetween('transaction_date', [$from_date, $to_date])
            ->sum('amount');

        $projected_net_position = $projected_inflows - $projected_outflows;
        $actual_net_position = $actual_cash_in - $actual_cash_out;

        $forecast_summary = FinanceCashFlowForecast::select(
                'forecast_type',
                DB::raw('SUM(expected_amount) as total_amount')
            )
            ->where('business_id', $business_id)
            ->whereBetween('expected_date', [$from_date, $to_date])
            ->groupBy('forecast_type')
            ->get();

        return view('finance::cash_flow_analytics.index')
            ->with(compact(
                'from_date',
                'to_date',
                'projected_inflows',
                'projected_outflows',
                'actual_cash_in',
                'actual_cash_out',
                'projected_net_position',
                'actual_net_position',
                'forecast_summary'
            ));
    }
}