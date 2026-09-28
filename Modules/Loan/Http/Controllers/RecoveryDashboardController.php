<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanPromiseToPay;
use Modules\Loan\Models\LoanRecoveryAssignment;

class RecoveryDashboardController
    extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Recovery Metrics
        |--------------------------------------------------------------------------
        */

        $assignedLoans =
            LoanRecoveryAssignment::count();

        $overdueLoans =
            LoanApplication::where(
                'status',
                'overdue'
            )->count();

        $npaLoans =
            LoanApplication::where(
                'dpd_bucket',
                'NPA'
            )->count();

        $brokenPromises =
            LoanPromiseToPay::where(
                'status',
                'broken'
            )->count();

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'loan::recovery.dashboard',
            compact(
                'assignedLoans',
                'overdueLoans',
                'npaLoans',
                'brokenPromises'
            )
        );
    }
}