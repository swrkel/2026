<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use App\User;

use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanRecoveryRemark;

class RecoveryTestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Enterprise Multi-Branch Recovery Dashboard
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

            if (is_array($decodedLocations)) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head Office / Super Admin Logic
        |--------------------------------------------------------------------------
        */

        $isHeadOfficeUser = false;

        if (empty($allowedLocations)) {

            $isHeadOfficeUser = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Base Loan Query
        |--------------------------------------------------------------------------
        */

        $loanBaseQuery =
            LoanApplication::query()
                ->where(
                    'business_id',
                    $businessId
                );

        /*
        |--------------------------------------------------------------------------
        | Branch Governance
        |--------------------------------------------------------------------------
        */

        if (!$isHeadOfficeUser) {

            $loanBaseQuery->whereIn(
                'location_id',
                $allowedLocations
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery Metrics
        |--------------------------------------------------------------------------
        */

        $totalLoans =
            (clone $loanBaseQuery)
                ->count();

        $overdueLoans =
            (clone $loanBaseQuery)
                ->where(
                    'dpd',
                    '>',
                    0
                )
                ->count();

        $dpd30 =
            (clone $loanBaseQuery)
                ->whereBetween(
                    'dpd',
                    [30, 59]
                )
                ->count();

        $dpd60 =
            (clone $loanBaseQuery)
                ->whereBetween(
                    'dpd',
                    [60, 89]
                )
                ->count();

        $dpd90 =
            (clone $loanBaseQuery)
                ->where(
                    'dpd',
                    '>=',
                    90
                )
                ->count();

        $totalOverdueLoans =
            $overdueLoans;

        $criticalLoans =
            $dpd90;

        $warningLoans =
            (clone $loanBaseQuery)
                ->whereBetween(
                    'dpd',
                    [30, 89]
                )
                ->count();

        $recoveryRemarks =
            LoanRecoveryRemark::where(
                'business_id',
                $businessId
            )
            ->count();

        $totalRecoveryRemarks =
            $recoveryRemarks;

        /*
        |--------------------------------------------------------------------------
        | Recovery Operations Summary
        |--------------------------------------------------------------------------
        */

        $totalRecoveryActions =
            $recoveryRemarks;

        $promiseToPayCases =
            LoanRecoveryRemark::where(
                'business_id',
                $businessId
            )
            ->where(
                'promise_to_pay',
                1
            )
            ->count();

        $followUpsToday =
            LoanRecoveryRemark::where(
                'business_id',
                $businessId
            )
            ->whereDate(
                'next_followup_date',
                now()->toDateString()
            )
            ->count();

        $activeOverdueCases =
            $overdueLoans;

        /*
        |--------------------------------------------------------------------------
        | Recovery Work Queue
        |--------------------------------------------------------------------------
        */

        $overdueLoanList =
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

        if (!$isHeadOfficeUser) {

            $overdueLoanList->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $overdueLoanList =
            $overdueLoanList
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
        | Recovery History
        |--------------------------------------------------------------------------
        */

        $recoveryHistory =
            LoanRecoveryRemark::leftJoin(
                'loan_applications',
                'loan_recovery_remarks.loan_application_id',
                '=',
                'loan_applications.id'
            )
            ->leftJoin(
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
                'loan_recovery_remarks.business_id',
                $businessId
            );

        if (!$isHeadOfficeUser) {

            $recoveryHistory->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $recoveryHistory =
            $recoveryHistory
                ->select(
                    'loan_recovery_remarks.*',
                    'contacts.name as customer_name',
                    'contacts.mobile as customer_mobile',
                    'business_locations.name as branch_name',
                    'loan_applications.application_no',
                    'loan_applications.dpd'
                )
                ->orderByDesc(
                    'loan_recovery_remarks.id'
                )
                ->paginate(15);

        /*
        |--------------------------------------------------------------------------
        | Branch KPI Summary
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
                $businessId
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

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view(
            'loan::recovery.test',
            compact(
                'totalLoans',
                'overdueLoans',
                'dpd30',
                'dpd60',
                'dpd90',
                'recoveryRemarks',
                'totalRecoveryActions',
                'promiseToPayCases',
                'followUpsToday',
                'activeOverdueCases',
                'totalOverdueLoans',
                'criticalLoans',
                'warningLoans',
                'totalRecoveryRemarks',
                'overdueLoanList',
                'recoveryHistory',
                'branchSummary',
                'allowedLocations',
                'isHeadOfficeUser'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Recovery Remark
    |--------------------------------------------------------------------------
    */

    public function storeRemark(Request $request)
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

                'remark' => [
                    'required',
                    'string',
                    'max:5000'
                ],

                'action_type' => [
                    'nullable',
                    'string'
                ],

                'next_followup_date' => [
                    'nullable',
                    'date'
                ]

            ]);

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
                        'Loan application not found.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Save Recovery Remark
            |--------------------------------------------------------------------------
            */

            LoanRecoveryRemark::create([

                'business_id' =>
                    $businessId,

                'loan_application_id' =>
                    $request->loan_application_id,

                'remark' =>
                    $request->remark,

                'action_type' =>
                    $request->action_type,

                'next_followup_date' =>
                    $request->next_followup_date,

                'created_by' =>
                    $userId
            ]);

            /*
            |--------------------------------------------------------------------------
            | Auto Recovery Status Logic
            |--------------------------------------------------------------------------
            */

            if ($loan->dpd >= 90) {

                $loan->recovery_status =
                    'legal_recovery';

            } elseif ($loan->dpd >= 60) {

                $loan->recovery_status =
                    'critical_recovery';

            } elseif ($loan->dpd >= 30) {

                $loan->recovery_status =
                    'warning_recovery';

            } else {

                $loan->recovery_status =
                    'normal_recovery';
            }

            $loan->save();

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Recovery remark saved successfully.'
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