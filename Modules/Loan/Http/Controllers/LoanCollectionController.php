<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanRecovery;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanRecoveryEscalation;

class LoanCollectionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Collection Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Collection Notes
        |--------------------------------------------------------------------------
        */

        $collection_notes =
            LoanCollectionNote::with([
                    'loan',
                    'customer',
                    'createdBy'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Collection KPIs
        |--------------------------------------------------------------------------
        */

        $total_collection_actions =
            LoanCollectionNote::where(
                'business_id',
                $business_id
            )->count();

        $promise_to_pay_count =
            LoanCollectionNote::where(
                'business_id',
                $business_id
            )
            ->whereNotNull(
                'promise_to_pay_date'
            )
            ->count();

        $follow_up_pending =
            LoanCollectionNote::where(
                'business_id',
                $business_id
            )
            ->whereDate(
                'follow_up_date',
                '<=',
                now()->toDateString()
            )
            ->count();

        $field_visits =
            LoanCollectionNote::where(
                'business_id',
                $business_id
            )
            ->where(
                'collection_type',
                'field_visit'
            )
            ->count();

        return view(
            'loan::loan_collections.index',
            compact(
                'collection_notes',
                'total_collection_actions',
                'promise_to_pay_count',
                'follow_up_pending',
                'field_visits'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Collection Note
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

                'collection_type' =>
                    'required',

                'note' =>
                    'required'
            ]);

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
                    $request->loan_id
                );

            /*
            |--------------------------------------------------------------------------
            | Collection Note
            |--------------------------------------------------------------------------
            */

            $collection_note =
                LoanCollectionNote::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'schedule_id' =>
                        $request->schedule_id,

                    'customer_id' =>
                        $loan->customer_id,

                    'collection_type' =>
                        $request->collection_type,

                    'note' =>
                        $request->note,

                    'promise_to_pay_date' =>
                        $request->promise_to_pay_date,

                    'follow_up_date' =>
                        $request->follow_up_date,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Recovery Fields
                    |--------------------------------------------------------------------------
                    */

                    'call_outcome' =>
                        $request->call_outcome,

                    'field_visit_required' =>
                        $request->field_visit_required
                        ? 1 : 0,

                    'escalation_required' =>
                        $request->escalation_required
                        ? 1 : 0,

                    'priority_level' =>
                        $request->priority_level
                        ?? 'normal',

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
                    'collection_action',

                'notes' =>
                    'Collection action recorded: '
                    . $request->collection_type,

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Escalation Engine
            |--------------------------------------------------------------------------
            */

            if (
                $request->escalation_required
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'escalation_level' =>
                        'level_1',

                    'reason' =>
                        'Collection escalation triggered',

                    'status' =>
                        'pending',

                    'created_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Notification Workflow
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
                    'collection_followup',

                'channel' =>
                    'sms',

                'message' =>
                    'Collection follow-up has been recorded on your loan account.',

                'status' =>
                    'pending'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Promise To Pay Intelligence
            |--------------------------------------------------------------------------
            */

            if (
                !empty(
                    $request->promise_to_pay_date
                )
            ) {

                LoanAuditLog::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'promise_to_pay_recorded',

                    'description' =>
                        'Promise to pay date recorded: '
                        . $request->promise_to_pay_date,

                    'performed_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Field Visit Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $request->field_visit_required
            ) {

                LoanAuditLog::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'field_visit_required',

                    'description' =>
                        'Field visit required for recovery action.',

                    'performed_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'action_type' =>
                    'collection_note_added',

                'description' =>
                    'Enterprise collection action recorded.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Collection recovery action recorded successfully.'
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
    | Collection Action Details
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $collection_note =
            LoanCollectionNote::with([

                    'loan',
                    'customer',
                    'createdBy'

                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_collections.show',
            compact('collection_note')
        );
    }
}