<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanOperationalRisk;

class LoanOperationalRiskController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Operational Risk Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Operational Risk Records
        |--------------------------------------------------------------------------
        */

        $risk_records =
            LoanOperationalRisk::where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Risk KPIs
        |--------------------------------------------------------------------------
        */

        $total_risk_records =
            LoanOperationalRisk::where(
                'business_id',
                $business_id
            )->count();

        $critical_incidents =
            LoanOperationalRisk::where(
                'business_id',
                $business_id
            )
            ->where(
                'severity_level',
                'critical'
            )
            ->count();

        $open_incidents =
            LoanOperationalRisk::where(
                'business_id',
                $business_id
            )
            ->where(
                'incident_status',
                'open'
            )
            ->count();

        $fraud_cases =
            LoanOperationalRisk::where(
                'business_id',
                $business_id
            )
            ->where(
                'risk_category',
                'fraud'
            )
            ->count();

        return view(
            'loan::loan_operational_risks.index',
            compact(

                'risk_records',

                'total_risk_records',
                'critical_incidents',
                'open_incidents',
                'fraud_cases'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Operational Risk Record
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

                'risk_category' =>
                    'required',

                'incident_title' =>
                    'required',

                'severity_level' =>
                    'required'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Reference Number
            |--------------------------------------------------------------------------
            */

            $reference_no =
                'OPR-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Risk Scoring
            |--------------------------------------------------------------------------
            */

            $risk_score = 25;

            if (
                $request->severity_level == 'critical'
            ) {

                $risk_score = 95;

            } elseif (
                $request->severity_level == 'high'
            ) {

                $risk_score = 75;

            } elseif (
                $request->severity_level == 'medium'
            ) {

                $risk_score = 50;
            }

            /*
            |--------------------------------------------------------------------------
            | Fraud Intelligence
            |--------------------------------------------------------------------------
            */

            $fraud_flag = 0;

            if (
                $request->risk_category == 'fraud'
            ) {

                $fraud_flag = 1;
            }

            /*
            |--------------------------------------------------------------------------
            | Control Governance
            |--------------------------------------------------------------------------
            */

            $control_status = 'pending';

            if (
                $request->severity_level == 'low'
            ) {

                $control_status = 'effective';
            }

            /*
            |--------------------------------------------------------------------------
            | Create Operational Risk Record
            |--------------------------------------------------------------------------
            */

            $risk =
                LoanOperationalRisk::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $request->loan_id,

                    'reference_no' =>
                        $reference_no,

                    'risk_category' =>
                        $request->risk_category,

                    'incident_title' =>
                        $request->incident_title,

                    'incident_description' =>
                        $request->incident_description,

                    'severity_level' =>
                        $request->severity_level,

                    'risk_score' =>
                        $risk_score,

                    'fraud_flag' =>
                        $fraud_flag,

                    'incident_status' =>
                        'open',

                    'control_status' =>
                        $control_status,

                    'control_testing_status' =>
                        $request->control_testing_status
                        ?? 'pending',

                    'control_certification_status' =>
                        $request->control_certification_status
                        ?? 'pending',

                    'operational_loss_amount' =>
                        $request->operational_loss_amount
                        ?? 0,

                    'detected_at' =>
                        now(),

                    'corrective_action_plan' =>
                        $request->corrective_action_plan,

                    'resolution_notes' =>
                        $request->resolution_notes,

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Escalation Workflow
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $request->severity_level,
                    ['critical', 'high']
                )
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $request->loan_id,

                    'escalation_type' =>
                        'operational_risk',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'Operational risk escalation triggered.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Fraud Monitoring Notification
            |--------------------------------------------------------------------------
            */

            if ($fraud_flag) {

                LoanNotification::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $request->loan_id,

                    'notification_type' =>
                        'fraud_risk_alert',

                    'channel' =>
                        'system',

                    'message' =>
                        'Fraud risk intelligence alert generated.',

                    'status' =>
                        'pending'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $request->loan_id,

                'action_type' =>
                    'operational_risk_created',

                'description' =>
                    'Operational risk governance incident created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Operational risk governance record created successfully.'
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
    | Operational Risk Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $risk =
            LoanOperationalRisk::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_operational_risks.show',
            compact('risk')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Operational Risk
    |--------------------------------------------------------------------------
    */

    public function resolve($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $risk =
                LoanOperationalRisk::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Resolution Workflow
            |--------------------------------------------------------------------------
            */

            $risk->incident_status =
                'resolved';

            $risk->control_status =
                'effective';

            $risk->control_testing_status =
                'completed';

            $risk->control_certification_status =
                'certified';

            $risk->resolved_at =
                now();

            $risk->resolved_by =
                $user_id;

            $risk->save();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $risk->loan_id,

                'action_type' =>
                    'operational_risk_resolved',

                'description' =>
                    'Operational risk governance incident resolved.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Operational risk issue resolved successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}