<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

use Modules\Loan\Models\LoanApplication;

class LoanDpdBucketService
{
    /*
    |--------------------------------------------------------------------------
    | Process DPD Buckets
    |--------------------------------------------------------------------------
    */

    public function process()
    {
        /*
        |--------------------------------------------------------------------------
        | Active Loans
        |--------------------------------------------------------------------------
        */

        $loans = LoanApplication::where(
            'status',
            '!=',
            'closed'
        )->get();

        foreach ($loans as $loan) {

            /*
            |--------------------------------------------------------------------------
            | Skip Missing Due Dates
            |--------------------------------------------------------------------------
            */

            if (!$loan->next_due_date) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate DPD
            |--------------------------------------------------------------------------
            */

            $dpd = 0;

            if (
                Carbon::parse(
                    $loan->next_due_date
                )->lt(now())
            ) {

                $dpd =
                    Carbon::parse(
                        $loan->next_due_date
                    )->diffInDays(now());
            }

            /*
            |--------------------------------------------------------------------------
            | DPD Classification
            |--------------------------------------------------------------------------
            */

            $bucket = 'CURRENT';

            if ($dpd >= 1 && $dpd <= 30) {

                $bucket = 'PAR_30';

            } elseif ($dpd >= 31 && $dpd <= 60) {

                $bucket = 'PAR_60';

            } elseif ($dpd >= 61 && $dpd <= 90) {

                $bucket = 'PAR_90';

            } elseif ($dpd > 90) {

                $bucket = 'NPA';
            }

            /*
            |--------------------------------------------------------------------------
            | Update Loan
            |--------------------------------------------------------------------------
            */

            $loan->update([

                'dpd' => $dpd,

                'dpd_bucket' => $bucket,

                'status' =>
                    $dpd > 0
                        ? 'overdue'
                        : 'active'
            ]);
        }
    }
}