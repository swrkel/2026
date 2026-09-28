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
use Modules\Loan\Models\LoanSettlement;

class LoanSettlementController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Settlement Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Settlement Records
        |--------------------------------------------------------------------------
        */

        $settlements =
            LoanSettlement::with([
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

        $total_settlements =
            LoanSettlement::where(
                'business_id',
                $business_id
            )->count();

        $pending_approvals =
            LoanSettlement::where(
                'business_id',
                $business_id
            )
            ->where(
                'approval_status',
                'pending'
            )
            ->count();

        $approved_settlements =
            LoanSettlement::where(
                'business_id',
                $business_id
            )
            ->where(
                'approval_status',
                'approved'
            )
            ->count();

        $restructured_loans =
            LoanSettlement::where(
                'business_id',
                $business_id
            )
            ->where(
                'settlement_type',
                'restructure'
            )
            ->count();

        return view(
            'loan::loan_settlements.index',
            compact(
                'settlements',
                'total_settlements',
                'pending_approvals',
                'approved_settlements',
                'restructured_loans'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Settlement
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

                'settlement_type' =>
                    'required',

                'proposed_amount' =>
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
            | Settlement Number
            |--------------------------------------------------------------------------
            */

            $settlement_no =
                'SET-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Settlement Intelligence Score
            |--------------------------------------------------------------------------
            */

            $recovery_score = 50;

            if (
                $loan->principal_outstanding > 0
            ) {

                $recovery_score =
                    round(
                        (
                            $request->proposed_amount /
                            $loan->principal_outstanding
                        ) * 100,
                        2
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Approval Status
            |--------------------------------------------------------------------------
            */

            $approval_status = 'pending';

            if (
                $recovery_score >= 80
            ) {

                $approval_status = 'approved';
            }

            /*
            |--------------------------------------------------------------------------
            | Installment Restructure Logic
            |--------------------------------------------------------------------------
            */

            $restructure_installments =
                $request->restructure_installments
                ?? null;

            $restructure_interest_rate =
                $request->restructure_interest_rate
                ?? null;

            /*
            |--------------------------------------------------------------------------
            | Create Settlement
            |--------------------------------------------------------------------------
            */

            $settlement =
                LoanSettlement::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'settlement_no' =>
                        $settlement_no,

                    'settlement_type' =>
                        $request->settlement_type,

                    'proposed_amount' =>
                        $request->proposed_amount,

                    'approved_amount' =>
                        $approval_status == 'approved'
                        ? $request->proposed_amount
                        : null,

                    'approval_status' =>
                        $approval_status,

                    'proposal_date' =>
                        now()->toDateString(),

                    'settlement_due_date' =>
                        $request->settlement_due_date,

                    'restructure_installments' =>
                        $restructure_installments,

                    'restructure_interest_rate' =>
                        $restructure_interest_rate,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Governance
                    |--------------------------------------------------------------------------
                    */

                    'recovery_score' =>
                        $recovery_score,

                    'requires_committee_approval' =>
                        $recovery_score < 80
                        ? 1 : 0,

                    'legal_clearance_required' =>
                        $request->legal_clearance_required
                        ? 1 : 0,

                    'settlement_risk_level' =>
                        $request->settlement_risk_level
                        ?? 'medium',

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Restructuring Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $request->settlement_type == 'restructure'
            ) {

                LoanRecovery::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'recovery_date' =>
                        now()->toDateString(),

                    'status' =>
                        'loan_restructured',

                    'notes' =>
                        'Loan restructuring initiated.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Settlement Governance Notes
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
                    'settlement_negotiation',

                'note' =>
                    $request->notes,

                'priority_level' =>
                    'high',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Escalation Synchronization
            |--------------------------------------------------------------------------
            */

            if (
                $recovery_score < 50
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'escalation_type' =>
                        'settlement_review',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'High-risk settlement requires escalation.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Legal Clearance Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $request->legal_clearance_required
            ) {

                LoanAuditLog::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'legal_clearance_required',

                    'description' =>
                        'Settlement requires legal clearance.',

                    'performed_by' =>
                        $user_id
                ]);
            }

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
                    'loan_settlement',

                'channel' =>
                    'system',

                'message' =>
                    'Your settlement proposal has been submitted for review.',

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
                    'loan_settlement_created',

                'description' =>
                    'Enterprise settlement proposal created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Settlement proposal created successfully.'
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
    | Settlement Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $settlement =
            LoanSettlement::with([

                    'loan',
                    'customer'

                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_settlements.show',
            compact('settlement')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Settlement
    |--------------------------------------------------------------------------
    */

    public function approve($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $settlement =
                LoanSettlement::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $settlement->approval_status =
                'approved';

            $settlement->approved_amount =
                $settlement->proposed_amount;

            $settlement->approved_by =
                $user_id;

            $settlement->approved_at =
                now();

            $settlement->save();

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $settlement->loan_id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'settlement_approved',

                'notes' =>
                    'Settlement proposal approved.',

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
                    $settlement->loan_id,

                'action_type' =>
                    'loan_settlement_approved',

                'description' =>
                    'Settlement approved successfully.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Settlement approved successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}