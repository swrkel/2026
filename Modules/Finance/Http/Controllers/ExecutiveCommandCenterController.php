<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use Modules\Finance\Entities\ExecutiveEarlyWarning;
use Modules\Finance\Entities\FinanceKpiSnapshot;
use Modules\Finance\Entities\FinanceLiquidityRisk;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

use Modules\Loan\Models\LoanCollectionWorkflow;
use Modules\Loan\Models\LoanRecoveryPriorityScore;
use Modules\Loan\Models\RecoveryOfficerProductivity;

class ExecutiveCommandCenterController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $latest_kpi = FinanceKpiSnapshot::where('business_id', $business_id)
            ->latest()
            ->first();

        $treasury_cash_in = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_in', 'bank_deposit'])
            ->sum('amount');

        $treasury_cash_out = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_out', 'bank_withdrawal'])
            ->sum('amount');

        $treasury_net_position = $treasury_cash_in - $treasury_cash_out;

        $open_warnings = ExecutiveEarlyWarning::where('business_id', $business_id)
            ->where('status', 'open')
            ->count();

        $critical_warnings = ExecutiveEarlyWarning::where('business_id', $business_id)
            ->where('severity', 'critical')
            ->count();

        $liquidity_risks = FinanceLiquidityRisk::where('business_id', $business_id)
            ->where('status', 'open')
            ->count();

        $critical_recovery_priorities = LoanRecoveryPriorityScore::where('business_id', $business_id)
            ->where('priority_level', 'critical')
            ->count();

        $open_collection_workflows = LoanCollectionWorkflow::where('business_id', $business_id)
            ->whereIn('status', ['open', 'followup', 'escalated'])
            ->count();

        $top_officers = RecoveryOfficerProductivity::where('business_id', $business_id)
            ->with('officer')
            ->orderBy('ranking_position')
            ->limit(5)
            ->get();

        $portfolio_exposure = 0;
        $overdue_exposure = 0;

        if (DB::getSchemaBuilder()->hasTable('loans')) {
            $portfolio_exposure = DB::table('loans')
                ->where('business_id', $business_id)
                ->sum('principal_amount');

            if (DB::getSchemaBuilder()->hasColumn('loans', 'overdue_amount')) {
                $overdue_exposure = DB::table('loans')
                    ->where('business_id', $business_id)
                    ->sum('overdue_amount');
            }
        }

        return view('finance::executive_command_center.index')
            ->with(compact(
                'latest_kpi',
                'treasury_cash_in',
                'treasury_cash_out',
                'treasury_net_position',
                'open_warnings',
                'critical_warnings',
                'liquidity_risks',
                'critical_recovery_priorities',
                'open_collection_workflows',
                'top_officers',
                'portfolio_exposure',
                'overdue_exposure'
            ));
    }
}