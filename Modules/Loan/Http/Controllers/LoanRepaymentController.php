<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRepayment;
use Modules\Loan\Models\LoanRepaymentSchedule;
use Modules\Loan\Models\LoanPenalty;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanAuditLog;

use DB;

class LoanRepaymentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Repayment Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $repayments = LoanRepayment::with([
                'loan',
                'schedule'
            ])
            ->where(
                'business_id',
                $business_id
            )
            ->latest()
            ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */

        $total_collections =
            LoanRepayment::where(
                'business_id',
                $business_id
            )->sum('amount');

        $today_collections =
            LoanRepayment::where(
                'business_id',
                $business_id
            )
            ->whereDate(
                'payment_date',
                now()->toDateString()
            )
            ->sum('amount');

        $repayment_count =
            LoanRepayment::where(
                'business_id',
                $business_id
            )->count();

        return view(
            'loan::loan_repayments.index',
            compact(
                'repayments',
                'total_collections',
                'today_collections',
                'repayment_count'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Repayment
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'schedule_id' =>
                    'required',

                'payment_date' =>
                    'required|date',

                'amount' =>
                    'required|numeric|min:1'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Schedule
            |--------------------------------------------------------------------------
            */

            $schedule =
                LoanRepaymentSchedule::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail(
                        $request->schedule_id
                    );

            /*
            |--------------------------------------------------------------------------
            | Loan
            |--------------------------------------------------------------------------
            */

            $loan = Loan::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail(
                    $schedule->loan_id
                );

            /*
            |--------------------------------------------------------------------------
            | Prevent Overpayment
            |--------------------------------------------------------------------------
            */

            if (
                $request->amount >
                $schedule->balance_amount
            ) {

                return redirect()
                    ->back()
                    ->withErrors(
                        'Payment exceeds outstanding balance.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Allocation Engine
            |--------------------------------------------------------------------------
            */

            $payment_amount =
                $request->amount;

            $penalty_paid = 0;

            $interest_paid = 0;

            $principal_paid = 0;

            /*
            |--------------------------------------------------------------------------
            | Penalty Allocation
            |--------------------------------------------------------------------------
            */

            if (
                $loan->penalty_outstanding > 0
            ) {

                $penalty_paid =
                    min(
                        $payment_amount,
                        $loan->penalty_outstanding
                    );

                $payment_amount -=
                    $penalty_paid;

                $loan->penalty_outstanding -=
                    $penalty_paid;

                $loan->penalty_paid +=
                    $penalty_paid;
            }

            /*
            |--------------------------------------------------------------------------
            | Interest Allocation
            |--------------------------------------------------------------------------
            */

            if (
                $payment_amount > 0
                &&
                $loan->interest_outstanding > 0
            ) {

                $interest_paid =
                    min(
                        $payment_amount,
                        $loan->interest_outstanding
                    );

                $payment_amount -=
                    $interest_paid;

                $loan->interest_outstanding -=
                    $interest_paid;

                $loan->interest_paid +=
                    $interest_paid;
            }

            /*
            |--------------------------------------------------------------------------
            | Principal Allocation
            |--------------------------------------------------------------------------
            */

            if (
                $payment_amount > 0
                &&
                $loan->principal_outstanding > 0
            ) {

                $principal_paid =
                    min(
                        $payment_amount,
                        $loan->principal_outstanding
                    );

                $payment_amount -=
                    $principal_paid;

                $loan->principal_outstanding -=
                    $principal_paid;

                $loan->principal_paid +=
                    $principal_paid;
            }

            /*
            |--------------------------------------------------------------------------
            | Store Repayment
            |--------------------------------------------------------------------------
            */

            $repayment =
                LoanRepayment::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'schedule_id' =>
                        $schedule->id,

                    'payment_date' =>
                        $request->payment_date,

                    'amount' =>
                        $request->amount,

                    'principal_paid' =>
                        $principal_paid,

                    'interest_paid' =>
                        $interest_paid,

                    'penalty_paid' =>
                        $penalty_paid,

                    'payment_method' =>
                        $request->payment_method
                        ?? 'cash',

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Schedule Reconciliation
            |--------------------------------------------------------------------------
            */

            $schedule->paid_amount +=
                $request->amount;

            $schedule->balance_amount -=
                $request->amount;

            /*
            |--------------------------------------------------------------------------
            | Status Reconciliation
            |--------------------------------------------------------------------------
            */

            if (
                $schedule->balance_amount <= 0
            ) {

                $schedule->status =
                    'paid';

            } elseif (
                $schedule->paid_amount > 0
            ) {

                $schedule->status =
                    'partial';
            }

            $schedule->save();

            /*
            |--------------------------------------------------------------------------
            | Loan Closure Engine
            |--------------------------------------------------------------------------
            */

            if (

                $loan->principal_outstanding <= 0
                &&
                $loan->interest_outstanding <= 0
                &&
                $loan->penalty_outstanding <= 0

            ) {

                $loan->status =
                    'closed';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Delinquency Cure
                |--------------------------------------------------------------------------
                */

                $overdue_count =
                    LoanRepaymentSchedule::where(
                            'loan_id',
                            $loan->id
                        )
                        ->where(
                            'status',
                            'overdue'
                        )
                        ->count();

                if (
                    $overdue_count == 0
                ) {

                    $loan->status =
                        'active';
                }
            }

            $loan->save();

            /*
            |--------------------------------------------------------------------------
            | Recovery Workflow Update
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'recovery_date' =>
                    $request->payment_date,

                'amount' =>
                    $request->amount,

                'status' =>
                    'collection_received',

                'notes' =>
                    'Loan repayment collected.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Notification Engine
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
                    'repayment_received',

                'channel' =>
                    'sms',

                'message' =>
                    'Loan repayment received successfully.',

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
                    'repayment_received',

                'description' =>
                    'Loan repayment collected and reconciled.',

                'performed_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Autonomous Penalty Resolution
            |--------------------------------------------------------------------------
            */

            LoanPenalty::where(
                    'loan_id',
                    $loan->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->update([

                    'status' =>
                        'resolved'
                ]);

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Loan repayment processed successfully.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return redirect()
                ->back()
                ->withErrors(
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Repayment Details
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $repayment = LoanRepayment::with([
                'loan',
                'schedule'
            ])
            ->where(
                'business_id',
                $business_id
            )
            ->findOrFail($id);

        return view(
            'loan::loan_repayments.show',
            compact('repayment')
        );
    }
}