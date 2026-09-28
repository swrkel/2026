<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanProduct;
use Modules\Loan\Models\Loan;
use Modules\Loan\Services\LoanScheduleService;

use App\User;
use Modules\Loan\Models\LoanCustomer;

use DB;

class LoanApplicationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Enterprise Multi-Branch Loan Application Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $user_id = request()->session()
            ->get('user.id');

        /*
        |--------------------------------------------------------------------------
        | Logged User
        |--------------------------------------------------------------------------
        */

        $user = User::find($user_id);

        /*
        |--------------------------------------------------------------------------
        | Multi Branch Access
        |--------------------------------------------------------------------------
        */

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (is_array($decodedLocations)) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head Office Logic
        |--------------------------------------------------------------------------
        */

        $isHeadOfficeUser = false;

        if (empty($allowedLocations)) {

            $isHeadOfficeUser = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Applications Query
        |--------------------------------------------------------------------------
        */

        $applications =
            LoanApplication::with([

                    'customer',
                    'loanProduct'

                ])
                ->where(
                    'business_id',
                    $business_id
                );

        /*
        |--------------------------------------------------------------------------
        | Branch Restriction
        |--------------------------------------------------------------------------
        */

        if (!$isHeadOfficeUser) {

            $applications->whereIn(
                'location_id',
                $allowedLocations
            );
        }

        $applications =
            $applications
                ->latest()
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | KPI Queries
        |--------------------------------------------------------------------------
        */

        $baseKpiQuery =
            LoanApplication::where(
                'business_id',
                $business_id
            );

        if (!$isHeadOfficeUser) {

            $baseKpiQuery->whereIn(
                'location_id',
                $allowedLocations
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard KPIs
        |--------------------------------------------------------------------------
        */

        $total_applications =
            (clone $baseKpiQuery)
                ->count();

        $approved_applications =
            (clone $baseKpiQuery)
                ->where(
                    'status',
                    'approved'
                )
                ->count();

        $rejected_applications =
            (clone $baseKpiQuery)
                ->where(
                    'status',
                    'rejected'
                )
                ->count();

        $disbursed_applications =
            (clone $baseKpiQuery)
                ->where(
                    'status',
                    'disbursed'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Branch Summary
        |--------------------------------------------------------------------------
        */

        $branchSummary =
            LoanApplication::leftJoin(
                    'business_locations',
                    'loan_applications.location_id',
                    '=',
                    'business_locations.id'
                )
                ->where(
                    'loan_applications.business_id',
                    $business_id
                );

        if (!$isHeadOfficeUser) {

            $branchSummary->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $branchSummary =
            $branchSummary
                ->select(

                    'business_locations.name as branch_name',

                    DB::raw(
                        'COUNT(loan_applications.id) as total_accounts'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_applications.status = "approved" THEN 1 ELSE 0 END) as approved_accounts'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_applications.status = "disbursed" THEN 1 ELSE 0 END) as disbursed_accounts'
                    )
                )
                ->groupBy(
                    'business_locations.name'
                )
                ->get();

        return view(
            'loan::loan_applications.index',
            compact(

                'applications',

                'total_applications',

                'approved_applications',

                'rejected_applications',

                'disbursed_applications',

                'branchSummary',

                'allowedLocations',

                'isHeadOfficeUser'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Application
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $user_id = request()->session()
            ->get('user.id');

        $user = User::find($user_id);

        /*
        |--------------------------------------------------------------------------
        | Multi Branch Access
        |--------------------------------------------------------------------------
        */

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (is_array($decodedLocations)) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Loan Products
        |--------------------------------------------------------------------------
        */

        $loan_products = LoanProduct::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'active'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        $customers = LoanCustomer::where(
                'business_id',
                $business_id
            )
            ->where(
                'status',
                'active'
            )
            ->orderBy('name')
            ->pluck(
                'name',
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | Branch Locations
        |--------------------------------------------------------------------------
        */

        $locations =
            DB::table('business_locations')
                ->where(
                    'business_id',
                    $business_id
                );

        if (!empty($allowedLocations)) {

            $locations->whereIn(
                'id',
                $allowedLocations
            );
        }

        $locations =
            $locations
                ->pluck(
                    'name',
                    'id'
                );

        /*
        |--------------------------------------------------------------------------
        | Risk Levels
        |--------------------------------------------------------------------------
        */

        $risk_levels = [

            'low' => 'Low Risk',

            'medium' => 'Medium Risk',

            'high' => 'High Risk',

            'critical' => 'Critical Risk',
        ];

        /*
        |--------------------------------------------------------------------------
        | Installment Frequencies
        |--------------------------------------------------------------------------
        */

        $installment_frequencies = [

            'daily' => 'Daily',

            'weekly' => 'Weekly',

            'biweekly' => 'Biweekly',

            'monthly' => 'Monthly',
        ];

        return view(
            'loan::loan_applications.create',
            compact(

                'loan_products',

                'customers',

                'risk_levels',

                'installment_frequencies',

                'locations'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Application
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $business_id = $request->session()
                ->get('user.business_id');

            $user_id = $request->session()
                ->get('user.id');

            $user = User::find($user_id);

            /*
            |--------------------------------------------------------------------------
            | Multi Branch Access
            |--------------------------------------------------------------------------
            */

            $allowedLocations = [];

            if (!empty($user->location_permissions)) {

                $decodedLocations =
                    json_decode(
                        $user->location_permissions,
                        true
                    );

                if (is_array($decodedLocations)) {

                    $allowedLocations =
                        $decodedLocations;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'location_id' =>
                    'required',

                'loan_product_id' =>
                    'required',

                'customer_id' =>
                    'required|integer|exists:loan_customers,id',

                'principal_amount' =>
                    'required|numeric|min:1',

                'interest_rate' =>
                    'required|numeric|min:0',

                'application_date' =>
                    'required|date',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Branch Governance Validation
            |--------------------------------------------------------------------------
            */

            if (
                !empty($allowedLocations)
            ) {

                if (
                    !in_array(
                        $request->location_id,
                        $allowedLocations
                    )
                ) {

                    return redirect()
                        ->back()
                        ->withErrors(
                            'You do not have branch access for this location.'
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Generate Application Number
            |--------------------------------------------------------------------------
            */

            $application_count =
                LoanApplication::where(
                    'business_id',
                    $business_id
                )->count();

            $application_no =
                'APP-' . str_pad(
                    $application_count + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            /*
            |--------------------------------------------------------------------------
            | Create Application
            |--------------------------------------------------------------------------
            */

            $application =
                LoanApplication::create([

                    'business_id' =>
                        $business_id,

                    'location_id' =>
                        $request->location_id,

                    'application_no' =>
                        $application_no,

                    'loan_product_id' =>
                        $request->loan_product_id,

                    'customer_id' =>
                        $request->customer_id,

                    'loan_customer_id' =>
                        $request->customer_id,

                    'principal_amount' =>
                        $request->principal_amount,

                    'interest_rate' =>
                        $request->interest_rate,

                    'interest_type' =>
                        $request->interest_type
                        ?? 'flat',

                    'tenure' =>
                        $request->tenure ?? 1,

                    'tenure_type' =>
                        $request->tenure_type
                        ?? 'months',

                    'installment_frequency' =>
                        $request->installment_frequency
                        ?? 'monthly',

                    'risk_level' =>
                        $request->risk_level
                        ?? 'low',

                    'application_date' =>
                        $request->application_date,

                    'notes' =>
                        $request->notes,

                    /*
                    |--------------------------------------------------------------------------
                    | Governance
                    |--------------------------------------------------------------------------
                    */

                    'workflow_stage' =>
                        'application_submitted',

                    'compliance_status' =>
                        'pending_review',

                    'collection_priority' =>
                        'normal',

                    /*
                    |--------------------------------------------------------------------------
                    | Status
                    |--------------------------------------------------------------------------
                    */

                    'status' => 'draft',

                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Upload Document
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('document_file')) {

                $file = $request->file(
                    'document_file'
                );

                $filename =
                    time() . '_' .
                    $file->getClientOriginalName();

                $path = $file->storeAs(
                    'loan_documents',
                    $filename,
                    'public'
                );

                \Modules\Loan\Models\LoanDocument::create([

                    'business_id' =>
                        $business_id,

                    'loan_application_id' =>
                        $application->id,

                    'document_name' =>
                        $filename,

                    'document_type' =>
                        $request->document_type,

                    'file_path' =>
                        $path,

                    'uploaded_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_application_id' =>
                    $application->id,

                'action_type' =>
                    'application_created',

                'description' =>
                    'Loan application created',

                'performed_by' =>
                    $user_id
            ]);

            DB::commit();

            return redirect(
                '/loan/loan-applications'
            )->with(
                'success',
                'Loan application created successfully'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Application
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $user_id = request()->session()
            ->get('user.id');

        $user = User::find($user_id);

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (is_array($decodedLocations)) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        $application =
            LoanApplication::with([

                    'customer',
                    'loanProduct'

                ])
                ->where(
                    'business_id',
                    $business_id
                );

        if (!empty($allowedLocations)) {

            $application->whereIn(
                'location_id',
                $allowedLocations
            );
        }

        $application =
            $application
                ->findOrFail($id);

        return view(
            'loan::loan_applications.show',
            compact('application')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Application
    |--------------------------------------------------------------------------
    */

    public function approve($id)
    {
        $business_id = request()->session()
            ->get('user.business_id');

        $user_id = request()->session()
            ->get('user.id');

        $user = User::find($user_id);

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (is_array($decodedLocations)) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        $application =
            LoanApplication::where(
                    'business_id',
                    $business_id
                );

        if (!empty($allowedLocations)) {

            $application->whereIn(
                'location_id',
                $allowedLocations
            );
        }

        $application =
            $application
                ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Maker Checker Governance
        |--------------------------------------------------------------------------
        */

        if (
            $application->created_by ==
            $user_id
        ) {

            return redirect()
                ->back()
                ->withErrors(
                    'Maker cannot approve own application'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Approve
        |--------------------------------------------------------------------------
        */

        $application->status =
            'approved';

        $application->workflow_stage =
            'credit_approved';

        $application->approved_by =
            $user_id;

        $application->approved_at =
            now();

        $application->updated_by =
            $user_id;

        $application->save();

        /*
        |--------------------------------------------------------------------------
        | Notification
        |--------------------------------------------------------------------------
        */

        \Modules\Loan\Models\LoanNotification::create([

            'business_id' =>
                $business_id,

            'loan_application_id' =>
                $application->id,

            'customer_id' =>
                $application->customer_id,

            'notification_type' =>
                'loan_approved',

            'channel' => 'sms',

            'message' =>
                'Your loan application has been approved.',

            'status' => 'pending'
        ]);

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        \Modules\Loan\Models\LoanAuditLog::create([

            'business_id' =>
                $business_id,

            'loan_application_id' =>
                $application->id,

            'action_type' =>
                'application_approved',

            'description' =>
                'Loan application approved',

            'performed_by' =>
                $user_id
        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan application approved successfully'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | Edit Application
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');
        $user = User::find($user_id);

        $allowedLocations = [];
        if (!empty($user->location_permissions)) {
            $decodedLocations = json_decode($user->location_permissions, true);
            if (is_array($decodedLocations)) {
                $allowedLocations = $decodedLocations;
            }
        }

        $applicationQuery = LoanApplication::where('business_id', $business_id);
        if (!empty($allowedLocations)) {
            $applicationQuery->whereIn('location_id', $allowedLocations);
        }

        $application = $applicationQuery->findOrFail($id);

        $loan_products = LoanProduct::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $customers = LoanCustomer::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        $locationsQuery = DB::table('business_locations')
            ->where('business_id', $business_id);

        if (!empty($allowedLocations)) {
            $locationsQuery->whereIn('id', $allowedLocations);
        }

        $locations = $locationsQuery->orderBy('name')->pluck('name', 'id');

        $risk_levels = [
            'low' => 'Low Risk',
            'medium' => 'Medium Risk',
            'high' => 'High Risk',
            'critical' => 'Critical Risk',
        ];

        $installment_frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'monthly' => 'Monthly',
        ];

        return view('loan::loan_applications.edit', compact(
            'application',
            'loan_products',
            'customers',
            'risk_levels',
            'installment_frequencies',
            'locations'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Application
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            $user = User::find($user_id);

            $allowedLocations = [];
            if (!empty($user->location_permissions)) {
                $decodedLocations = json_decode($user->location_permissions, true);
                if (is_array($decodedLocations)) {
                    $allowedLocations = $decodedLocations;
                }
            }

            $request->validate([
                'location_id' => 'required',
                'loan_product_id' => 'required',
                'customer_id' => 'required|integer|exists:loan_customers,id',
                'principal_amount' => 'required|numeric|min:1',
                'interest_rate' => 'required|numeric|min:0',
                'application_date' => 'required|date',
            ]);

            if (!empty($allowedLocations) && !in_array($request->location_id, $allowedLocations)) {
                return redirect()->back()->withErrors('You do not have branch access for this location.')->withInput();
            }

            $applicationQuery = LoanApplication::where('business_id', $business_id);
            if (!empty($allowedLocations)) {
                $applicationQuery->whereIn('location_id', $allowedLocations);
            }
            $application = $applicationQuery->findOrFail($id);

            if (!in_array($application->status, ['draft', 'rejected'])) {
                return redirect()->back()->withErrors('Only draft or rejected applications can be edited.');
            }

            $application->update([
                'location_id' => $request->location_id,
                'loan_product_id' => $request->loan_product_id,
                'customer_id' => $request->customer_id,
                'loan_customer_id' => $request->customer_id,
                'principal_amount' => $request->principal_amount,
                'interest_rate' => $request->interest_rate,
                'interest_type' => $request->interest_type ?? 'flat',
                'tenure' => $request->tenure ?? 1,
                'tenure_type' => $request->tenure_type ?? 'months',
                'installment_frequency' => $request->installment_frequency ?? 'monthly',
                'risk_level' => $request->risk_level ?? 'low',
                'application_date' => $request->application_date,
                'notes' => $request->notes,
                'workflow_stage' => 'application_updated',
                'compliance_status' => 'pending_review',
                'updated_by' => $user_id,
            ]);

            if ($request->hasFile('document_file')) {
                $file = $request->file('document_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('loan_documents', $filename, 'public');

                \Modules\Loan\Models\LoanDocument::create([
                    'business_id' => $business_id,
                    'loan_application_id' => $application->id,
                    'document_name' => $filename,
                    'document_type' => $request->document_type,
                    'file_path' => $path,
                    'uploaded_by' => $user_id,
                ]);
            }

            \Modules\Loan\Models\LoanAuditLog::create([
                'business_id' => $business_id,
                'loan_application_id' => $application->id,
                'action_type' => 'application_updated',
                'description' => 'Loan application updated',
                'performed_by' => $user_id,
            ]);

            DB::commit();

            return redirect('/loan/loan-applications')->with('success', 'Loan application updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e);
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Application
    |--------------------------------------------------------------------------
    */

    public function reject($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');
        $user = User::find($user_id);

        $allowedLocations = [];
        if (!empty($user->location_permissions)) {
            $decodedLocations = json_decode($user->location_permissions, true);
            if (is_array($decodedLocations)) {
                $allowedLocations = $decodedLocations;
            }
        }

        $applicationQuery = LoanApplication::where('business_id', $business_id);
        if (!empty($allowedLocations)) {
            $applicationQuery->whereIn('location_id', $allowedLocations);
        }

        $application = $applicationQuery->findOrFail($id);

        if (!in_array($application->status, ['draft', 'pending', 'submitted'])) {
            return redirect()->back()->withErrors('Only pending or draft applications can be rejected.');
        }

        $application->status = 'rejected';
        $application->workflow_stage = 'application_rejected';
        $application->rejected_by = $user_id;
        $application->rejected_at = now();
        $application->updated_by = $user_id;
        $application->save();

        \Modules\Loan\Models\LoanAuditLog::create([
            'business_id' => $business_id,
            'loan_application_id' => $application->id,
            'action_type' => 'application_rejected',
            'description' => 'Loan application rejected',
            'performed_by' => $user_id,
        ]);

        return redirect()->back()->with('success', 'Loan application rejected successfully');
    }


    /*
    |--------------------------------------------------------------------------
    | Disburse Loan
    |--------------------------------------------------------------------------
    */

    public function disburse($id)
    {
        try {

            DB::beginTransaction();

            $business_id = request()->session()
                ->get('user.business_id');

            $user_id = request()->session()
                ->get('user.id');

            $user = User::find($user_id);

            $allowedLocations = [];

            if (!empty($user->location_permissions)) {

                $decodedLocations =
                    json_decode(
                        $user->location_permissions,
                        true
                    );

                if (is_array($decodedLocations)) {

                    $allowedLocations =
                        $decodedLocations;
                }
            }

            $application =
                LoanApplication::where(
                        'business_id',
                        $business_id
                    );

            if (!empty($allowedLocations)) {

                $application->whereIn(
                    'location_id',
                    $allowedLocations
                );
            }

            $application =
                $application
                    ->findOrFail($id);

            /*
            |--------------------------------------------------------------------------
            | Approval Validation
            |--------------------------------------------------------------------------
            */

            if (
                $application->status !=
                'approved'
            ) {

                return redirect()
                    ->back()
                    ->withErrors(
                        'Only approved applications can be disbursed'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Generate Loan Number
            |--------------------------------------------------------------------------
            */

            $loan_count = Loan::where(
                'business_id',
                $business_id
            )->count();

            $loan_no =
                'LOAN-' . str_pad(
                    $loan_count + 1,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            /*
            |--------------------------------------------------------------------------
            | Create Loan
            |--------------------------------------------------------------------------
            */

            $loan = Loan::create([

                'business_id' =>
                    $application->business_id,

                'location_id' =>
                    $application->location_id,

                'loan_application_id' =>
                    $application->id,

                'loan_no' =>
                    $loan_no,

                'customer_id' =>
                    $application->loan_customer_id ?? $application->customer_id,

                'loan_customer_id' =>
                    $application->loan_customer_id ?? $application->customer_id,

                'loan_product_id' =>
                    $application->loan_product_id,

                'principal_amount' =>
                    $application->principal_amount,

                'principal_paid' => 0,

                'interest_paid' => 0,

                'penalty_paid' => 0,

                'principal_outstanding' =>
                    $application->principal_amount,

                'interest_outstanding' =>
                    (
                        $application->principal_amount *
                        $application->interest_rate / 100
                    ),

                'penalty_outstanding' => 0,

                'interest_rate' =>
                    $application->interest_rate,

                'interest_type' =>
                    $application->interest_type,

                'tenure' =>
                    $application->tenure,

                'tenure_type' =>
                    $application->tenure_type,

                'installment_frequency' =>
                    $application->installment_frequency,

                'risk_level' =>
                    $application->risk_level,

                'disbursement_date' =>
                    now()->toDateString(),

                'status' => 'active',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Generate Repayment Schedules
            |--------------------------------------------------------------------------
            */

            $schedule_service =
                new LoanScheduleService();

            $schedule_service
                ->generateSchedules($loan);

            /*
            |--------------------------------------------------------------------------
            | Accounting Hook
            |--------------------------------------------------------------------------
            */

            if (function_exists('module_enabled')) {

                if (module_enabled('Accounting')) {

                    /*
                    |--------------------------------------------------------------------------
                    | Future Accounting Integration
                    |--------------------------------------------------------------------------
                    */
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Application
            |--------------------------------------------------------------------------
            */

            $application->status =
                'disbursed';

            $application->workflow_stage =
                'loan_disbursed';

            $application->updated_by =
                $user_id;

            $application->save();

            /*
            |--------------------------------------------------------------------------
            | Notification
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanNotification::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'loan_application_id' =>
                    $application->id,

                'customer_id' =>
                    $application->loan_customer_id ?? $application->customer_id,

                'notification_type' =>
                    'loan_disbursed',

                'channel' => 'sms',

                'message' =>
                    'Your loan has been disbursed successfully.',

                'status' => 'pending'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            \Modules\Loan\Models\LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'loan_application_id' =>
                    $application->id,

                'action_type' =>
                    'loan_disbursed',

                'description' =>
                    'Loan disbursed successfully',

                'performed_by' =>
                    $user_id
            ]);

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Loan disbursed successfully'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}