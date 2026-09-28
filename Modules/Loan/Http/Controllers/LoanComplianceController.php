<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanCompliance;

class LoanComplianceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Compliance Governance Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Compliance Records
        |--------------------------------------------------------------------------
        */

        $compliance_records =
            LoanCompliance::where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Compliance KPIs
        |--------------------------------------------------------------------------
        */

        $total_compliance_records =
            LoanCompliance::where(
                'business_id',
                $business_id
            )->count();

        $open_breaches =
            LoanCompliance::where(
                'business_id',
                $business_id
            )
            ->where(
                'compliance_status',
                'breach'
            )
            ->count();

        $pending_audits =
            LoanCompliance::where(
                'business_id',
                $business_id
            )
            ->where(
                'audit_status',
                'pending'
            )
            ->count();

        $resolved_issues =
            LoanCompliance::where(
                'business_id',
                $business_id
            )
            ->where(
                'issue_resolution_status',
                'resolved'
            )
            ->count();

        return view(
            'loan::loan_compliance.index',
            compact(

                'compliance_records',

                'total_compliance_records',
                'open_breaches',
                'pending_audits',
                'resolved_issues'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Compliance Record
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

                'compliance_type' =>
                    'required',

                'regulatory_body' =>
                    'required',

                'compliance_status' =>
                    'required'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Compliance Reference
            |--------------------------------------------------------------------------
            */

            $reference_no =
                'CMP-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Risk Classification
            |--------------------------------------------------------------------------
            */

            $risk_level = 'low';

            if (
                $request->compliance_status == 'breach'
            ) {

                $risk_level = 'high';

            } elseif (
                $request->compliance_status == 'warning'
            ) {

                $risk_level = 'medium';
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Governance
            |--------------------------------------------------------------------------
            */

            $audit_status = 'pending';

            if (
                $request->compliance_status == 'compliant'
            ) {

                $audit_status = 'completed';
            }

            /*
            |--------------------------------------------------------------------------
            | Create Compliance Record
            |--------------------------------------------------------------------------
            */

            $compliance =
                LoanCompliance::create([

                    'business_id' =>
                        $business_id,

                    'reference_no' =>
                        $reference_no,

                    'compliance_type' =>
                        $request->compliance_type,

                    'regulatory_body' =>
                        $request->regulatory_body,

                    'compliance_status' =>
                        $request->compliance_status,

                    'risk_level' =>
                        $risk_level,

                    'audit_status' =>
                        $audit_status,

                    'issue_resolution_status' =>
                        $request->issue_resolution_status
                        ?? 'open',

                    'inspection_date' =>
                        $request->inspection_date,

                    'compliance_due_date' =>
                        $request->compliance_due_date,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Governance
                    |--------------------------------------------------------------------------
                    */

                    'requires_board_attention' =>
                        $risk_level == 'high'
                        ? 1 : 0,

                    'regulatory_escalation_required' =>
                        $request->compliance_status == 'breach'
                        ? 1 : 0,

                    'certification_status' =>
                        $request->certification_status
                        ?? 'pending',

                    'audit_findings' =>
                        $request->audit_findings,

                    'corrective_action_plan' =>
                        $request->corrective_action_plan,

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
                $risk_level == 'high'
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'escalation_type' =>
                        'compliance_breach',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'Critical compliance breach detected.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Compliance Notification
            |--------------------------------------------------------------------------
            */

            LoanNotification::create([

                'business_id' =>
                    $business_id,

                'notification_type' =>
                    'compliance_update',

                'channel' =>
                    'system',

                'message' =>
                    'Compliance governance workflow updated.',

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
                    'compliance_record_created',

                'description' =>
                    'Enterprise compliance governance record created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Compliance governance record created successfully.'
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
    | Compliance Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $compliance =
            LoanCompliance::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_compliance.show',
            compact('compliance')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Compliance Issue
    |--------------------------------------------------------------------------
    */

    public function resolve($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $compliance =
                LoanCompliance::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Resolution Workflow
            |--------------------------------------------------------------------------
            */

            $compliance->issue_resolution_status =
                'resolved';

            $compliance->audit_status =
                'completed';

            $compliance->compliance_status =
                'compliant';

            $compliance->resolved_at =
                now();

            $compliance->resolved_by =
                $user_id;

            $compliance->save();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'action_type' =>
                    'compliance_issue_resolved',

                'description' =>
                    'Compliance governance issue resolved.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Compliance issue resolved successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}