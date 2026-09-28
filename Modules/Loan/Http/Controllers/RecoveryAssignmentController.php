<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use App\User;

use Modules\Loan\Models\LoanApplication;

class RecoveryAssignmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Enterprise Multi-Branch Recovery Assignment Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $businessId = request()
            ->session()
            ->get('user.business_id');

        $userId = request()
            ->session()
            ->get('user.id');

        /*
        |--------------------------------------------------------------------------
        | Logged User
        |--------------------------------------------------------------------------
        */

        $user = User::find($userId);

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

            if (
                is_array($decodedLocations)
            ) {

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
        | Recovery Officers
        |--------------------------------------------------------------------------
        */

        $recoveryOfficers =
            User::where(
                    'business_id',
                    $businessId
                )
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'location_permissions'
                )
                ->orderBy(
                    'first_name'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Overdue Loans Queue
        |--------------------------------------------------------------------------
        */

        $overdueLoans =
            LoanApplication::leftJoin(
                    'contacts',
                    'loan_applications.customer_id',
                    '=',
                    'contacts.id'
                )
                ->leftJoin(
                    'business_locations',
                    'loan_applications.location_id',
                    '=',
                    'business_locations.id'
                )
                ->where(
                    'loan_applications.business_id',
                    $businessId
                )
                ->where(
                    'loan_applications.dpd',
                    '>',
                    0
                );

        /*
        |--------------------------------------------------------------------------
        | Branch Restriction
        |--------------------------------------------------------------------------
        */

        if (!$isHeadOfficeUser) {

            $overdueLoans->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $overdueLoans =
            $overdueLoans
                ->select(

                    'loan_applications.*',

                    'contacts.name as customer_name',

                    'contacts.mobile as customer_mobile',

                    'business_locations.name as branch_name'
                )
                ->orderByDesc(
                    'loan_applications.dpd'
                )
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Branch Assignment Summary
        |--------------------------------------------------------------------------
        */

        $branchAssignmentSummary =
            LoanApplication::leftJoin(
                    'business_locations',
                    'loan_applications.location_id',
                    '=',
                    'business_locations.id'
                )
                ->where(
                    'loan_applications.business_id',
                    $businessId
                );

        if (!$isHeadOfficeUser) {

            $branchAssignmentSummary->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $branchAssignmentSummary =
            $branchAssignmentSummary
                ->select(

                    'business_locations.name as branch_name',

                    DB::raw(
                        'COUNT(loan_applications.id) as total_accounts'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_applications.dpd > 0 THEN 1 ELSE 0 END) as overdue_accounts'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_applications.dpd >= 90 THEN 1 ELSE 0 END) as legal_accounts'
                    )
                )
                ->groupBy(
                    'business_locations.name'
                )
                ->get();

        return view(
            'loan::recovery.assignments',
            compact(

                'recoveryOfficers',

                'overdueLoans',

                'branchAssignmentSummary',

                'allowedLocations',

                'isHeadOfficeUser'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Assignment
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $businessId = $request
                ->session()
                ->get('user.business_id');

            $userId = $request
                ->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'loan_application_id' => [
                    'required',
                    'integer'
                ],

                'recovery_officer_id' => [
                    'required',
                    'integer'
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:5000'
                ]

            ]);

            /*
            |--------------------------------------------------------------------------
            | Logged User
            |--------------------------------------------------------------------------
            */

            $loggedUser = User::find($userId);

            /*
            |--------------------------------------------------------------------------
            | Multi Branch Access
            |--------------------------------------------------------------------------
            */

            $allowedLocations = [];

            if (
                !empty(
                    $loggedUser->location_permissions
                )
            ) {

                $decodedLocations =
                    json_decode(
                        $loggedUser->location_permissions,
                        true
                    );

                if (
                    is_array($decodedLocations)
                ) {

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
            | Verify Loan
            |--------------------------------------------------------------------------
            */

            $loan =
                LoanApplication::where(
                        'business_id',
                        $businessId
                    )
                    ->where(
                        'id',
                        $request->loan_application_id
                    )
                    ->first();

            if (!$loan) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Loan account not found.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Branch Governance Validation
            |--------------------------------------------------------------------------
            */

            if (
                !$isHeadOfficeUser
            ) {

                if (
                    !in_array(
                        $loan->location_id,
                        $allowedLocations
                    )
                ) {

                    return redirect()
                        ->back()
                        ->with(
                            'error',
                            'You do not have branch access to assign this loan.'
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Recovery Officer Validation
            |--------------------------------------------------------------------------
            */

            $officer =
                User::where(
                        'business_id',
                        $businessId
                    )
                    ->where(
                        'id',
                        $request->recovery_officer_id
                    )
                    ->first();

            if (!$officer) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'Recovery officer not found.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Active Assignment Prevention
            |--------------------------------------------------------------------------
            */

            $existingAssignment =
                DB::table(
                    'loan_recovery_assignments'
                )
                ->where(
                    'loan_application_id',
                    $loan->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();

            if ($existingAssignment) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'This loan already has an active assignment.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Create Assignment
            |--------------------------------------------------------------------------
            */

            DB::table(
                'loan_recovery_assignments'
            )->insert([

                'loan_application_id' =>
                    $loan->id,

                'recovery_officer_id' =>
                    $request->recovery_officer_id,

                'status' =>
                    'active',

                'assigned_at' =>
                    now(),

                'notes' =>
                    $request->notes,

                'created_at' =>
                    now(),

                'updated_at' =>
                    now()
            ]);

            /*
            |--------------------------------------------------------------------------
            | Recovery Status Automation
            |--------------------------------------------------------------------------
            */

            if ($loan->dpd >= 90) {

                $loan->recovery_status =
                    'legal_assignment';

            } elseif ($loan->dpd >= 60) {

                $loan->recovery_status =
                    'critical_assignment';

            } elseif ($loan->dpd >= 30) {

                $loan->recovery_status =
                    'warning_assignment';

            } else {

                $loan->recovery_status =
                    'normal_assignment';
            }

            $loan->save();

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Recovery officer assigned successfully.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }
}