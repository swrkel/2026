<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanRecoveryAssignment;

class RecoveryOfficerQueueController
    extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Assigned Accounts
        |--------------------------------------------------------------------------
        */

        $assignments =
            LoanRecoveryAssignment::with([
                'loan',
                'loan.customer'
            ])
            ->where(
                'status',
                'active'
            )
            ->latest()
            ->get();

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'loan::recovery.queue',
            compact(
                'assignments'
            )
        );
    }
}