<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanWriteOff;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanRecoveryEscalation;

class LoanWriteOffController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Write-Off Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Write-Off Records
        |--------------------------------------------------------------------------
        */

        $write_offs =
            LoanWriteOff::with([
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

        $total_write_offs =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )->count();

        $pending_approvals =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )
            ->where(
                'approval_status',
                'pending'
            )
            ->count();

        $approved_write_offs =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )
            ->where(
                'approval_status',
                'approved'
            )
            ->count();

        $total_loss_amount =
            LoanWriteOff::where(
                'business_id',
                $business_id
            )
            ->sum('write_off_amount');

        return view(
            'loan::loan_write_offs.index',
            compact(
                'write_offs',
                'total_write_offs',
                'pending_approvals',
                'approved_write_offs',
                'total_loss_amount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Write-Off
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

                'write_off_reason' =>
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
            | Write-Off Number
            |--------------------------------------------------------------------------
            */

            $write_off_no =
                'WO-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Outstanding Balance
            |--------------------------------------------------------------------------
            */

            $write_off_amount =
                $loan->principal_outstanding
                ?? $loan->principal_amount;

            /*
            |--------------------------------------------------------------------------
            | Provisioning Score
            |--------------------------------------------------------------------------
            */

            $provisioning_score = 100;

            if (
                $loan->status == 'active'
            ) {

                $provisioning_score = 60;

            } elseif (
                $loan->status == 'defaulted'
            ) {

                $provisioning_score = 90;
            }

            /*
            |--------------------------------------------------------------------------
            | Approval Governance
            |--------------------------------------------------------------------------
            */

            $approval_status = 'pending';

            if (
                $write_off_amount < 50000
            ) {

                $approval_status = 'approved';
            }

            /*
            |--------------------------------------------------------------------------
            | Create Write-Off
            |--------------------------------------------------------------------------
            */

            $write_off =
                LoanWriteOff::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'write_off_no' =>
                        $write_off_no,

                    'write_off_reason' =>
                        $request->write_off_reason,

                    'write_off_amount' =>
                        $write_off_amount,

                    'recovered_amount' =>
                        $request->recovered_amount
                        ?? 0,

                    'loss_amount' =>
                        $write_off_amount
                        - (
                            $request->recovered_amount
                            ?? 0
                        ),

                    'write_off_date' =>
                        now()->toDateString(),

                    'approval_status' =>
                        $approval_status,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Governance
                    |--------------------------------------------------------------------------
                    */

                    'provisioning_score' =>
                        $provisioning_score,

                    'requires_board_approval' =>
                        $write_off_amount >= 50000
                        ? 1 : 0,

                    'legal_clearance_required' =>
                        $request->legal_clearance_required
                        ? 1 : 0,

                    'recovery_closure_status' =>
                        'pending',

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Loan Status Update
            |--------------------------------------------------------------------------
            */

            if (
                $approval_status == 'approved'
            ) {

                $loan->status =
                    'written_off';

                $loan->save();
            }

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow
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
                    'write_off_initiated',

                'notes' =>
                    'Enterprise write-off workflow initiated.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Collection Governance Notes
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
                    'write_off',

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
            | Escalation Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $write_off_amount >= 50000
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'escalation_type' =>
                        'write_off_board_review',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'Board approval required for write-off.',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Legal Governance
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
                        'write_off_legal_clearance',

                    'description' =>
                        'Legal clearance required for write-off.',

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
                    'loan_write_off',

                'channel' =>
                    'system',

                'message' =>
                    'Your loan account has entered write-off governance review.',

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
                    'loan_write_off_created',

                'description' =>
                    'Enterprise write-off case created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Write-off governance record created successfully.'
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
    | Write-Off Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $write_off =
            LoanWriteOff::with([

                    'loan',
                    'customer'

                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_write_offs.show',
            compact('write_off')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Write-Off
    |--------------------------------------------------------------------------
    */

    public function approve($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $write_off =
                LoanWriteOff::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Approval Workflow
            |--------------------------------------------------------------------------
            */

            $write_off->approval_status =
                'approved';

            $write_off->approved_by =
                $user_id;

            $write_off->approved_at =
                now();

            $write_off->recovery_closure_status =
                'closed';

            $write_off->save();

            /*
            |--------------------------------------------------------------------------
            | Loan Closure
            |--------------------------------------------------------------------------
            */

            $loan =
                Loan::find($write_off->loan_id);

            if ($loan) {

                $loan->status =
                    'written_off';

                $loan->save();
            }

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $write_off->loan_id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'write_off_approved',

                'notes' =>
                    'Enterprise write-off approved.',

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
                    $write_off->loan_id,

                'action_type' =>
                    'loan_write_off_approved',

                'description' =>
                    'Write-off approved successfully.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Write-off approved successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}