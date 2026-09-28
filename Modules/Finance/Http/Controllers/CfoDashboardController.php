<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\FinanceBranchScore;
use Modules\Finance\Entities\FinanceKpiSnapshot;
use Modules\Finance\Entities\FinanceLiquidityRisk;
use Modules\Finance\Entities\FinanceRiskAlert;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

class CfoDashboardController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        /*
        |--------------------------------------------------------------------------
        | Latest KPI Snapshot
        |--------------------------------------------------------------------------
        */

        $latest_kpi = FinanceKpiSnapshot::where(
            'business_id',
            $business_id
        )
        ->latest()
        ->first();

        /*
        |--------------------------------------------------------------------------
        | Treasury Position
        |--------------------------------------------------------------------------
        */

        $treasury_cash_in = FinanceTreasuryTransaction::where(
            'business_id',
            $business_id
        )
        ->whereIn('treasury_type', [
            'cash_in',
            'bank_deposit'
        ])
        ->sum('amount');

        $treasury_cash_out = FinanceTreasuryTransaction::where(
            'business_id',
            $business_id
        )
        ->whereIn('treasury_type', [
            'cash_out',
            'bank_withdrawal'
        ])
        ->sum('amount');

        $treasury_net_position =
            $treasury_cash_in - $treasury_cash_out;

        /*
        |--------------------------------------------------------------------------
        | Risk & Liquidity Alerts
        |--------------------------------------------------------------------------
        */

        $open_risk_alerts = FinanceRiskAlert::where(
            'business_id',
            $business_id
        )
        ->where('status', 'open')
        ->count();

        $critical_risk_alerts = FinanceRiskAlert::where(
            'business_id',
            $business_id
        )
        ->where('risk_level', 'critical')
        ->count();

        $open_liquidity_risks = FinanceLiquidityRisk::where(
            'business_id',
            $business_id
        )
        ->where('status', 'open')
        ->count();

        /*
        |--------------------------------------------------------------------------
        | Latest Branch Scores
        |--------------------------------------------------------------------------
        */

        $top_branches = FinanceBranchScore::where(
            'business_id',
            $business_id
        )
        ->with('location')
        ->orderBy('ranking_position')
        ->limit(5)
        ->get();

        /*
        |--------------------------------------------------------------------------
        | Receivables & Payables Exposure
        |--------------------------------------------------------------------------
        */

        $receivables = 0;
        $payables = 0;

        if (
            DB::getSchemaBuilder()
                ->hasTable('transactions')
        ) {

            $receivables = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'sell')
                ->whereIn('payment_status', [
                    'due',
                    'partial'
                ])
                ->sum('final_total');

            $payables = DB::table('transactions')
                ->where('business_id', $business_id)
                ->where('type', 'purchase')
                ->whereIn('payment_status', [
                    'due',
                    'partial'
                ])
                ->sum('final_total');
        }

        return view('finance::cfo_dashboard.index')
            ->with(compact(
                'latest_kpi',
                'treasury_cash_in',
                'treasury_cash_out',
                'treasury_net_position',
                'open_risk_alerts',
                'critical_risk_alerts',
                'open_liquidity_risks',
                'top_branches',
                'receivables',
                'payables'
            ));
    }
}