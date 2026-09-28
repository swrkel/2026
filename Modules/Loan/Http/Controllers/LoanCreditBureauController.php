<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanCreditBureau;

class LoanCreditBureauController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Credit Bureau Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Bureau Records
        |--------------------------------------------------------------------------
        */

        $bureau_records =
            LoanCreditBureau::with([
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

        $total_records =
            LoanCreditBureau::where(
                'business_id',
                $business_id
            )->count();

        $high_risk_customers =
            LoanCreditBureau::where(
                'business_id',
                $business_id
            )
            ->where(
                'risk_level',
                'high'
            )
            ->count();

        $bureau_defaulters =
            LoanCreditBureau::where(
                'business_id',
                $business_id
            )
            ->where(
                'bureau_status',
                'defaulted'
            )
            ->count();

        $active_syncs =
            LoanCreditBureau::where(
                'business_id',
                $business_id
            )
            ->where(
                'sync_status',
                'synced'
            )
            ->count();

        return view(
            'loan::loan_credit_bureaus.index',
            compact(
                'bureau_records',
                'total_records',
                'high_risk_customers',
                'bureau_defaulters',
                'active_syncs'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Bureau Record
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

                'bureau_name' =>
                    'required',

                'credit_score' =>
                    'required',

                'bureau_status' =>
                    'required'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate Loan
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
            | Risk Intelligence
            |--------------------------------------------------------------------------
            */

            $risk_level = 'low';

            if (
                $request->credit_score < 500
            ) {

                $risk_level = 'high';

            } elseif (
                $request->credit_score < 700
            ) {

                $risk_level = 'medium';
            }

            /*
            |--------------------------------------------------------------------------
            | Exposure Intelligence
            |--------------------------------------------------------------------------
            */

            $external_exposure =
                $request->external_exposure
                ?? 0;

            $total_exposure =
                (
                    $loan->principal_outstanding
                    ?? 0
                ) + $external_exposure;

            /*
            |--------------------------------------------------------------------------
            | Bureau Reference
            |--------------------------------------------------------------------------
            */

            $reference_no =
                'CB-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Create Bureau Record
            |--------------------------------------------------------------------------
            */

            $bureau_record =
                LoanCreditBureau::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'reference_no' =>
                        $reference_no,

                    'bureau_name' =>
                        $request->bureau_name,

                    'credit_score' =>
                        $request->credit_score,

                    'bureau_status' =>
                        $request->bureau_status,

                    'risk_level' =>
                        $risk_level,

                    'external_exposure' =>
                        $external_exposure,

                    'total_exposure' =>
                        $total_exposure,

                    'active_loans_count' =>
                        $request->active_loans_count
                        ?? 0,

                    'defaulted_loans_count' =>
                        $request->defaulted_loans_count
                        ?? 0,

                    'delinquency_days' =>
                        $request->delinquency_days
                        ?? 0,

                    /*
                    |--------------------------------------------------------------------------
                    | Enterprise Intelligence
                    |--------------------------------------------------------------------------
                    */

                    'behavior_score' =>
                        $request->behavior_score
                        ?? 50,

                    'multi_lender_risk' =>
                        $request->multi_lender_risk
                        ? 1 : 0,

                    'external_collection_flag' =>
                        $request->external_collection_flag
                        ? 1 : 0,

                    'bureau_sync_date' =>
                        now()->toDateString(),

                    'sync_status' =>
                        'synced',

                    'notes' =>
                        $request->notes,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | High Risk Escalation
            |--------------------------------------------------------------------------
            */

            if (
                $risk_level == 'high'
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'escalation_type' =>
                        'credit_bureau_risk',

                    'priority_level' =>
                        'critical',

                    'status' =>
                        'open',

                    'notes' =>
                        'High external bureau risk detected.',

                    'created_by' =>
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
                    'credit_bureau_sync',

                'channel' =>
                    'system',

                'message' =>
                    'Your account has been synchronized with external credit bureau systems.',

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
                    'credit_bureau_synced',

                'description' =>
                    'External credit bureau profile synchronized.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Credit bureau profile synchronized successfully.'
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
    | Bureau Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $bureau_record =
            LoanCreditBureau::with([

                    'loan',
                    'customer'

                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_credit_bureaus.show',
            compact('bureau_record')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Re-Synchronize Bureau
    |--------------------------------------------------------------------------
    */

    public function resync($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $bureau_record =
                LoanCreditBureau::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Bureau Sync
            |--------------------------------------------------------------------------
            */

            $bureau_record->bureau_sync_date =
                now()->toDateString();

            $bureau_record->sync_status =
                'synced';

            $bureau_record->save();

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $bureau_record->loan_id,

                'action_type' =>
                    'credit_bureau_resynced',

                'description' =>
                    'Credit bureau profile re-synchronized.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Credit bureau synchronized successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}