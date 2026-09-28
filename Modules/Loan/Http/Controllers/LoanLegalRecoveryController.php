<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanLegalRecovery;

class LoanLegalRecoveryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Legal Recovery Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Legal Recovery Cases
        |--------------------------------------------------------------------------
        */

        $legal_cases =
            LoanLegalRecovery::with([
                    'loan',
                    'customer'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | KPI Metrics
        |--------------------------------------------------------------------------
        */

        $total_cases =
            LoanLegalRecovery::where(
                'business_id',
                $business_id
            )->count();

        $active_cases =
            LoanLegalRecovery::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'active'
            )
            ->count();

        $court_cases =
            LoanLegalRecovery::where(
                'business_id',
                $business_id
            )
            ->where(
                'legal_stage',
                'court'
            )
            ->count();

        $closed_cases =
            LoanLegalRecovery::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'closed'
            )
            ->count();

        return view(
            'loan::loan_legal_recoveries.index',
            compact(
                'legal_cases',
                'total_cases',
                'active_cases',
                'court_cases',
                'closed_cases'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Legal Recovery Case
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

                'loan_id' =>
                    'required',

                'case_title' =>
                    'required',

                'legal_stage' =>
                    'required',

                'notes' =>
                    'required'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Loan Validation
            |--------------------------------------------------------------------------
            */

            $loan = Loan::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail(
                    $request->loan_id
                );

            /*
            |--------------------------------------------------------------------------
            | Case Number
            |--------------------------------------------------------------------------
            */

            $case_no =
                'LEGAL-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Court Hearing Date
            |--------------------------------------------------------------------------
            */

            $hearing_date = null;

            if (
                $request->legal_stage == 'court'
            ) {

                $hearing_date =
                    now()->addDays(30)
                        ->toDateString();
            }

            /*
            |--------------------------------------------------------------------------
            | Create Legal Recovery Case
            |--------------------------------------------------------------------------
            */

            $legal_case =
                LoanLegalRecovery::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'case_no' =>
                        $case_no,

                    'case_title' =>
                        $request->case_title,

                    'legal_stage' =>
                        $request->legal_stage,

                    'assigned_to' =>
                        $request->assigned_to,

                    'court_name' =>
                        $request->court_name,

                    'court_hearing_date' =>
                        $hearing_date,

                    'claim_amount' =>
                        $request->claim_amount
                        ?? $loan->principal_outstanding,

                    'status' =>
                        'active',

                    'notes' =>
                        $request->notes,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Legal Intelligence
                    |--------------------------------------------------------------------------
                    */

                    'priority_level' =>
                        $request->priority_level
                        ?? 'high',

                    'legal_cost_estimate' =>
                        $request->legal_cost_estimate
                        ?? 0,

                    'settlement_allowed' =>
                        $request->settlement_allowed
                        ? 1 : 0,

                    'recovery_probability' =>
                        $request->recovery_probability
                        ?? 50,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow Tracking
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'legal_recovery_started',

                'notes' =>
                    'Legal recovery case initiated.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Escalation Synchronization
            |--------------------------------------------------------------------------
            */

            LoanRecoveryEscalation::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'escalation_type' =>
                    'legal',

                'priority_level' =>
                    'critical',

                'status' =>
                    'open',

                'notes' =>
                    'Legal recovery escalation created.',

                'legal_action_required' =>
                    1,

                'escalation_date' =>
                    now()->toDateString(),

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Collection Recovery Notes
            |--------------------------------------------------------------------------
            */

            LoanCollectionNote::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'collection_type' =>
                    'legal_action',

                'note' =>
                    $request->notes,

                'priority_level' =>
                    'critical',

                'escalation_required' =>
                    1,

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Customer Notification
            |--------------------------------------------------------------------------
            */

            LoanNotification::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'notification_type' =>
                    'legal_recovery',

                'channel' =>
                    'system',

                'message' =>
                    'Your loan account has entered legal recovery workflow.',

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

                'loan_id' =>
                    $loan->id,

                'action_type' =>
                    'legal_recovery_created',

                'description' =>
                    'Enterprise legal recovery case created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Legal recovery case created successfully.'
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
    | Legal Recovery Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $legal_case =
            LoanLegalRecovery::with([

                    'loan',
                    'customer'

                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_legal_recoveries.show',
            compact('legal_case')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Close Legal Recovery Case
    |--------------------------------------------------------------------------
    */

    public function close($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $legal_case =
                LoanLegalRecovery::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Close Case
            |--------------------------------------------------------------------------
            */

            $legal_case->status =
                'closed';

            $legal_case->closed_at =
                now();

            $legal_case->closed_by =
                $user_id;

            $legal_case->save();

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $legal_case->loan_id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'legal_case_closed',

                'notes' =>
                    'Legal recovery case closed.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $legal_case->loan_id,

                'action_type' =>
                    'legal_recovery_closed',

                'description' =>
                    'Legal recovery case closed.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Legal recovery case closed successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}