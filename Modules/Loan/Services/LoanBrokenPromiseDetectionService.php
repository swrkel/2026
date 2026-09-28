<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

use Modules\Loan\Models\LoanPromiseToPay;

class LoanBrokenPromiseDetectionService
{
    /*
    |--------------------------------------------------------------------------
    | Detect Broken Promises
    |--------------------------------------------------------------------------
    */

    public function process()
    {
        /*
        |--------------------------------------------------------------------------
        | Get Pending Promises
        |--------------------------------------------------------------------------
        */

        $promises =
            LoanPromiseToPay::where(
                'status',
                'pending'
            )->get();

        foreach ($promises as $promise) {

            /*
            |--------------------------------------------------------------------------
            | Detect Broken Promise
            |--------------------------------------------------------------------------
            */

            if (
                Carbon::parse(
                    $promise->promised_date
                )->lt(now())
            ) {

                $promise->update([

                    'status' => 'broken',

                    'broken_at' => now(),

                ]);

                /*
                |--------------------------------------------------------------------------
                | Escalate Loan Status
                |--------------------------------------------------------------------------
                */

                if ($promise->loan) {

                    $promise->loan->update([

                        'loan_status' =>
                            'recovery_escalated'

                    ]);
                }
            }
        }
    }
}