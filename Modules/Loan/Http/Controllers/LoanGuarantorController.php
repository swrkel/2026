<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanGuarantor;

class LoanGuarantorController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guarantor Governance Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Guarantor Records
        |--------------------------------------------------------------------------
        */

        $guarantors =
            LoanGuarantor::where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Guarantor KPIs
        |--------------------------------------------------------------------------
        */

        $total_guarantors =
            LoanGuarantor::where(
                'business_id',
                $business_id
            )->count();

        $active_guarantors =
            LoanGuarantor::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'active'
            )
            ->count();

        $high_exposure_guarantors =
            LoanGuarantor::where(
                'business_id',
                $business_id
            )
            ->where(
                'exposure_risk_level',
                'high'
            )
            ->count();

        $legal_action_guarantors =
            LoanGuarantor::where(
                'business_id',
                $business_id
            )
            ->where(
                'legal_action_status',
                'initiated'
            )
            ->count();

        return view(
            'loan::loan_guarantor.index',
            compact(

                'guarantors',

                'total_guarantors',
                'active_guarantors',
                'high_exposure_guarantors',
                'legal_action_guarantors'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Guarantor Record
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

                'guarantor_name' =>
                    'required',

                'guarantor_type' =>
                    'required',

                'guarantee_amount' =>
                    'required|numeric'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validate Loan
            |--------------------------------------------------------------------------
            */

            $loan =
                Loan::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail(
                        $request->loan_id
                    );

            /*
            |--------------------------------------------------------------------------
            | Reference Number
            |--------------------------------------------------------------------------
            */

            $reference_no =
                'GUA-'
                . date('Ymd')
                . '-'
                . rand(1000, 9999);

            /*
            |--------------------------------------------------------------------------
            | Exposure Analytics
            |--------------------------------------------------------------------------
            */

            $guarantee_amount =
                $request->guarantee_amount;

            $loan_outstanding =
                $loan->principal_outstanding;

            $coverage_ratio = 0;

            if ($loan_outstanding > 0) {

                $coverage_ratio =
                    round(
                        (
                            $guarantee_amount /
                            $loan_outstanding
                        ) * 100,
                        2
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Risk Classification
            |--------------------------------------------------------------------------
            */

            $exposure_risk_level = 'low';

            if ($coverage_ratio < 50) {

                $exposure_risk_level = 'critical';

            } elseif ($coverage_ratio < 75) {

                $exposure_risk_level = 'high';

            } elseif ($coverage_ratio < 100) {

                $exposure_risk_level = 'medium';
            }

            /*
            |--------------------------------------------------------------------------
            | Liability Governance
            |--------------------------------------------------------------------------
            */

            $liability_status = 'active';

            $legal_action_status = 'not_required';

            if (
                $loan->status == 'defaulted'
            ) {

                $legal_action_status = 'review_pending';
            }

            /*
            |--------------------------------------------------------------------------
            | Exposure Aggregation
            |--------------------------------------------------------------------------
            */

            $existing_exposure =
                LoanGuarantor::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'national_id',
                    $request->national_id
                )
                ->sum('guarantee_amount');

            $total_exposure =
                $existing_exposure +
                $guarantee_amount;

            /*
            |--------------------------------------------------------------------------
            | Create Guarantor Record
            |--------------------------------------------------------------------------
            */

            $guarantor =
                LoanGuarantor::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'reference_no' =>
                        $reference_no,

                    'guarantor_name' =>
                        $request->guarantor_name,

                    'guarantor_type' =>
                        $request->guarantor_type,

                    'national_id' =>
                        $request->national_id,

                    'phone_number' =>
                        $request->phone_number,

                    'email' =>
                        $request->email,

                    'address' =>
                        $request->address,

                    /*
                    |--------------------------------------------------------------------------
                    | Financial Governance
                    |--------------------------------------------------------------------------
                    */

                    'guarantee_amount' =>
                        $guarantee_amount,

                    'coverage_ratio' =>
                        $coverage_ratio,

                    'total_exposure' =>
                        $total_exposure,

                    'monthly_income' =>
                        $request->monthly_income
                        ?? 0,

                    'net_worth' =>
                        $request->net_worth
                        ?? 0,

                    /*
                    |--------------------------------------------------------------------------
                    | Governance Intelligence
                    |--------------------------------------------------------------------------
                    */

                    'exposure_risk_level' =>
                        $exposure_risk_level,

                    'liability_status' =>
                        $liability_status,

                    'legal_action_status' =>
                        $legal_action_status,

                    'status' =>
                        'active',

                    'co_borrower_flag' =>
                        $request->co_borrower_flag
                        ?? 0,

                    'guarantee_start_date' =>
                        now()->toDateString(),

                    'guarantee_expiry_date' =>
                        $request->guarantee_expiry_date,

                    /*
                    |--------------------------------------------------------------------------
                    | Legal Governance
                    |--------------------------------------------------------------------------
                    */

                    'legal_document_reference' =>
                        $request->legal_document_reference,

                    'legal_enforceability_status' =>
                        $request->legal_enforceability_status
                        ?? 'pending',

                    /*
                    |--------------------------------------------------------------------------
                    | Executive Governance
                    |--------------------------------------------------------------------------
                    */

                    'executive_attention_required' =>
                        $exposure_risk_level == 'critical'
                        ? 1 : 0,

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
                    $exposure_risk_level,
                    ['critical', 'high']
                )
            ) {

                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'escalation_type' =>
                        'guarantor_exposure_risk',

                    'priority_level' =>
                        'high',

                    'status' =>
                        'open',

                    'notes' =>
                        'Guarantor exposure governance escalation triggered.',

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

                'notification_type' =>
                    'guarantor_governance',

                'channel' =>
                    'system',

                'message' =>
                    'Guarantor governance workflow updated.',

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
                    'guarantor_record_created',

                'description' =>
                    'Guarantor governance record created.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Guarantor governance record created successfully.'
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
    | Guarantor Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $guarantor =
            LoanGuarantor::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail($id);

        return view(
            'loan::loan_guarantor.show',
            compact('guarantor')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Legal Enforcement Workflow
    |--------------------------------------------------------------------------
    */

    public function initiateLegalAction($id)
    {
        try {

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $guarantor =
                LoanGuarantor::where(
                        'business_id',
                        $business_id
                    )
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Legal Governance
            |--------------------------------------------------------------------------
            */

            $guarantor->legal_action_status =
                'initiated';

            $guarantor->legal_action_date =
                now();

            $guarantor->liability_status =
                'under_legal_recovery';

            $guarantor->save();

            /*
            |--------------------------------------------------------------------------
            | Escalation Workflow
            |--------------------------------------------------------------------------
            */

            LoanRecoveryEscalation::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $guarantor->loan_id,

                'customer_id' =>
                    $guarantor->customer_id,

                'escalation_type' =>
                    'guarantor_legal_action',

                'priority_level' =>
                    'critical',

                'status' =>
                    'open',

                'notes' =>
                    'Legal enforcement initiated against guarantor.',

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
                    $guarantor->loan_id,

                'action_type' =>
                    'guarantor_legal_action_initiated',

                'description' =>
                    'Guarantor legal enforcement workflow initiated.',

                'performed_by' =>
                    $user_id
            ]);

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Guarantor legal action initiated successfully.'
                );

        } catch (\Exception $e) {

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}