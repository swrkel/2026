<?php

namespace Modules\Loan\Console;

use Illuminate\Console\Command;

use Carbon\Carbon;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanPenalty;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanRepaymentSchedule;
use Modules\Loan\Models\LoanAuditLog;

use DB;

class ProcessOverdueLoans extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Command Signature
    |--------------------------------------------------------------------------
    */

    protected $signature =
        'loan:process-overdue';

    /*
    |--------------------------------------------------------------------------
    | Description
    |--------------------------------------------------------------------------
    */

    protected $description =
        'Process overdue loan schedules and penalties';

    /*
    |--------------------------------------------------------------------------
    | Execute
    |--------------------------------------------------------------------------
    */

    public function handle()
    {
        $this->info(
            'Starting overdue loan processing...'
        );

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Find Overdue Schedules
            |--------------------------------------------------------------------------
            */

            $schedules =
                LoanRepaymentSchedule::whereIn(
                        'status',
                        [
                            'pending',
                            'partial'
                        ]
                    )
                    ->whereDate(
                        'due_date',
                        '<',
                        now()->toDateString()
                    )
                    ->get();

            $processed = 0;

            foreach ($schedules as $schedule) {

                /*
                |--------------------------------------------------------------------------
                | DPD Calculation
                |--------------------------------------------------------------------------
                */

                $dpd =
                    Carbon::parse(
                        $schedule->due_date
                    )->diffInDays(now());

                /*
                |--------------------------------------------------------------------------
                | Mark Overdue
                |--------------------------------------------------------------------------
                */

                $schedule->status =
                    'overdue';

                $schedule->days_past_due =
                    $dpd;

                $schedule->save();

                /*
                |--------------------------------------------------------------------------
                | Loan
                |--------------------------------------------------------------------------
                */

                $loan =
                    Loan::find(
                        $schedule->loan_id
                    );

                if (!$loan) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Loan Status Escalation
                |--------------------------------------------------------------------------
                */

                if ($dpd >= 90) {

                    $loan->status =
                        'npa';

                } elseif ($dpd >= 30) {

                    $loan->status =
                        'delinquent';

                } elseif ($dpd >= 1) {

                    $loan->status =
                        'overdue';
                }

                /*
                |--------------------------------------------------------------------------
                | Collection Priority Escalation
                |--------------------------------------------------------------------------
                */

                if ($dpd >= 90) {

                    $loan->collection_priority =
                        'critical';

                } elseif ($dpd >= 30) {

                    $loan->collection_priority =
                        'high';

                } elseif ($dpd >= 7) {

                    $loan->collection_priority =
                        'medium';

                } else {

                    $loan->collection_priority =
                        'normal';
                }

                /*
                |--------------------------------------------------------------------------
                | Penalty Calculation
                |--------------------------------------------------------------------------
                */

                $penalty_amount =
                    round(
                        $schedule->balance_amount
                        * 0.02,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Penalties
                |--------------------------------------------------------------------------
                */

                $existing_penalty =
                    LoanPenalty::where(
                            'loan_id',
                            $loan->id
                        )
                        ->where(
                            'schedule_id',
                            $schedule->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->exists();

                if (!$existing_penalty) {

                    LoanPenalty::create([

                        'business_id' =>
                            $loan->business_id,

                        'loan_id' =>
                            $loan->id,

                        'schedule_id' =>
                            $schedule->id,

                        'penalty_name' =>
                            'Overdue Penalty',

                        'penalty_type' =>
                            'percentage',

                        'penalty_value' =>
                            2,

                        'amount' =>
                            $penalty_amount,

                        'status' =>
                            'active',

                        'notes' =>
                            'Auto-generated overdue penalty.',

                        'created_by' =>
                            1
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Update Loan Penalty Outstanding
                    |--------------------------------------------------------------------------
                    */

                    $loan->penalty_outstanding +=
                        $penalty_amount;
                }

                /*
                |--------------------------------------------------------------------------
                | Save Loan
                |--------------------------------------------------------------------------
                */

                $loan->save();

                /*
                |--------------------------------------------------------------------------
                | Notification Engine
                |--------------------------------------------------------------------------
                */

                LoanNotification::create([

                    'business_id' =>
                        $loan->business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'notification_type' =>
                        'loan_overdue',

                    'channel' =>
                        'sms',

                    'message' =>
                        'Your loan installment is overdue.',

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
                        $loan->business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'loan_overdue_processed',

                    'description' =>
                        'Loan overdue processing completed automatically.',

                    'performed_by' =>
                        1
                ]);

                $processed++;
            }

            DB::commit();

            $this->info(
                $processed .
                ' overdue schedules processed successfully.'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            $this->error(
                $e->getMessage()
            );
        }
    }
}