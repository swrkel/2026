<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

use Modules\Loan\Models\LoanRepaymentSchedule;

class LoanOverdueService
{
    /**
     * Update overdue schedules.
     */
    public function updateOverdues()
    {
        $today = Carbon::today();

        $schedules = LoanRepaymentSchedule::where(
                'status',
                '!=',
                'paid'
            )
            ->get();

        foreach ($schedules as $schedule) {

            /*
            |--------------------------------------------------------------------------
            | Reset defaults
            |--------------------------------------------------------------------------
            */

            $schedule->days_overdue = 0;

            $schedule->is_overdue = 0;

            $schedule->overdue_amount = 0;

            /*
            |--------------------------------------------------------------------------
            | Detect overdue schedules
            |--------------------------------------------------------------------------
            */

            if (
                $schedule->due_date < $today &&
                $schedule->balance_amount > 0
            ) {

                $days_overdue =
                    $today->diffInDays(
                        Carbon::parse(
                            $schedule->due_date
                        )
                    );

                $schedule->days_overdue =
                    $days_overdue;

                $schedule->is_overdue = 1;

                $schedule->overdue_amount =
                    $schedule->balance_amount;

                /*
                |--------------------------------------------------------------------------
                | Auto-update status
                |--------------------------------------------------------------------------
                */

                $schedule->status = 'overdue';
            }

            $schedule->save();
        }
    }
}