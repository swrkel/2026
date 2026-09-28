<?php

namespace Modules\Loan\Services;

use App\User;

use Modules\Loan\Models\LoanApplication;
use Modules\Loan\Models\LoanRecoveryAssignment;

class LoanRecoveryAssignmentService
{
    /*
    |--------------------------------------------------------------------------
    | Auto Assign Overdue Loans
    |--------------------------------------------------------------------------
    */

    public function autoAssign()
    {
        /*
        |--------------------------------------------------------------------------
        | Get Recovery Officers
        |--------------------------------------------------------------------------
        */

        $officers = User::role(
            'recovery_officer'
        )->get();

        if ($officers->count() == 0) {

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Unassigned Overdue Loans
        |--------------------------------------------------------------------------
        */

        $loans = LoanApplication::where(
            'loan_status',
            'overdue'
        )
        ->whereDoesntHave(
            'activeRecoveryAssignment'
        )
        ->get();

        /*
        |--------------------------------------------------------------------------
        | Round Robin Assignment
        |--------------------------------------------------------------------------
        */

        $counter = 0;

        foreach ($loans as $loan) {

            $officer =
                $officers[
                    $counter %
                    $officers->count()
                ];

            LoanRecoveryAssignment::create([

                'loan_application_id' =>
                    $loan->id,

                'recovery_officer_id' =>
                    $officer->id,

                'status' => 'active',

                'assigned_at' => now(),

            ]);

            $counter++;
        }
    }
}
