<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanRepayment;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanTreasury;
use Modules\Loan\Models\LoanRecoveryEscalation;

class LoanTreasuryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Treasury Governance Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Treasury Records
        |--------------------------------------------------------------------------
        */

        $treasury_records =
            LoanTreasury::where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Liquidity KPIs
        |--------------------------------------------------------------------------
        */

        $total_treasury_records =
            LoanTreasury::where(
                'business_id',
                $business_id
            )->count();

        $total_liquidity =
            LoanTreasury::where(
                'business_id',
                $business_id
            )->sum('available_liquidity');

        $cashflow_exposure =
            LoanTreasury::where(
                'business_id',
                $business_id
            )->sum('cashflow_exposure');

        $funding_gap =
            LoanTreasury::where(
                'business_id',
                $business_id
            )->sum('funding_gap');

        return view(
            'loan::loan_treasury.index',
            compact(

                'treasury_records',

                'total_treasury_records',
                'total_liquidity',
                'cashflow_exposure',
                'funding_gap'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Treasury Governance Record
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        try {

            $business_id = $request->session()
                ->get('user.business_id');

            $user_id = $request->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'treasury_type' =>
                    'required',

                'available_liquidity' =>
                    'required',

                'cashflow_projection' =>
                    'required'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Treasury Reference
            |--------------------------------------------------------------------------
            */

            $reference_no =
                'TRY-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Exposure Analytics
            |--------------------------------------------------------------------------
            */

            $cashflow_projection =
                $request->cashflow_projection;

            $available_liquidity =
                $request->available_liquidity;

            $cashflow_exposure =
                max(
                    0,
                    $cashflow_projection -
                    $available_liquidity
                );

            $funding_gap =
                $cashflow_exposure;

            /*
            |--------------------------------------------------------------------------
            | Liquidity Risk Classification
            |--------------------------------------------------------------------------
            */

            $liquidity_risk_level = 'low';

            if ($funding_gap > 1000000) {

                $liquidity_risk_level = 'critical';

            } elseif ($funding_gap > 500000) {

                $liquidity_risk_level = 'high';

            } elseif ($funding_gap > 100000) {

                $liquidity_risk_level = 'medium';
            }

            /*
            |--------------------------------------------------------------------------
            | Treasury Governance Status
            |--------------------------------------------------------------------------
            */

            $governance_status = 'stable';

            if (
                in_array(
                    $liquidity_risk_level,
                    ['critical', 'high']
                )
            ) {

                $governance_status = 'escalated';
            }

            /*
            |--------------------------------------------------------------------------
            | Recovery Cashflow Intelligence
            |--------------------------------------------------------------------------
            */

            $expected_recoveries =
                LoanRecovery::where(
                    'business_id',
                    $business_id
                )
                ->sum('recovered_amount');

            $repayment_inflows =
                LoanRepayment::where(
                    'business_id',
                    $business_id
                )
                ->sum('amount');

            /*
            |--------------------------------------------------------------------------
            | Create Treasury Record
            |--------------------------------------------------------------------------
            */

            $treasury =
                LoanTreasury::create([

                    'business_id' =>
                        $business_id,

                    'reference_no' =>
                        $reference_no,

                    'treasury_type' =>
                        $request->treasury_type,

                    'available_liquidity' =>
                        $available_liquidity,

                    'cashflow_projection' =>
                        $cashflow_projection,

                    'cashflow_exposure' =>
                        $cashflow_exposure,

                    'funding_gap' =>
                        $funding_gap,

                    'liquidity_risk_level' =>
                        $liquidity_risk_level,

                    'governance_status' =>
                        $governance_status,

                    /*
                    |--------------------------------------------------------------------------
                    | Treasury Intelligence
                    |--------------------------------------------------------------------------
                    */

                    'expected_recoveries' =>
                        $expected_recoveries,

                    'repayment_inflows' =>
                        $repayment_inflows,

                    'liquidity_buffer' =>
                        $request->liquidity_buffer
                        ?? 0,

                    'stress_test_status' =>
                        $request->stress_test_status
                        ?? 'pending',

                    'cashflow_forecast_status' =>
                        'generated',

                    'funding_source' =>
                        $request->funding_source,

                    'treasury_notes' =>
                        $request->treasury_notes,

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Liquidity Escalation
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $liquidity_risk_level,
                    ['critical', 'high']
                )
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'escalation_type' =>
                        'liquidity_risk',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'Treasury liquidity risk escalation triggered.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Treasury Notification
            |--------------------------------------------------------------------------
            */

            LoanNotification::create([

                'business_id' =>
                    $business_id,

                'notification_type' =>
                    'treasury_governance',

                'channel' =>
                    'system',

                'message' =>
                    'Treasury governance workflow updated successfully.',

                'status' =>
                    'pending'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'action_type' =>
                    'treasury_record_created',

                'description' =>
                    'Treasury liquidity governance record created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Treasury governance record created successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Treasury Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $treasury =
            LoanTreasury::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_treasury.show',
            compact('treasury')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Liquidity Stress Test
    |--------------------------------------------------------------------------
    */

    public function stressTest($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $treasury =
                LoanTreasury::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Stress Test Simulation
            |--------------------------------------------------------------------------
            */

            $stress_exposure =
                $treasury->cashflow_projection * 1.25;

            $stress_gap =
                $stress_exposure -
                $treasury->available_liquidity;

            $treasury->stress_test_status =
                'completed';

            $treasury->stress_test_result =
                $stress_gap > 0
                ? 'liquidity_pressure_detected'
                : 'stable';

            $treasury->stress_test_gap =
                $stress_gap;

            $treasury->stress_tested_at =
                now();

            $treasury->save();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'action_type' =>
                    'treasury_stress_test_completed',

                'description' =>
                    'Treasury liquidity stress test completed.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Treasury stress test completed successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}