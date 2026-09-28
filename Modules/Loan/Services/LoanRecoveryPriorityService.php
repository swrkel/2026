<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecoveryPriorityScore;

class LoanRecoveryPriorityService
{
    public function generate($business_id)
    {
        /*
        |--------------------------------------------------------------------------
        | CLEAR OPEN SCORES
        |--------------------------------------------------------------------------
        */

        LoanRecoveryPriorityScore::where(
            'business_id',
            $business_id
        )->where(
            'status',
            'open'
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | GET LOANS
        |--------------------------------------------------------------------------
        */

        $loans = Loan::where(
            'business_id',
            $business_id
        )->get();

        foreach ($loans as $loan) {

            /*
            |--------------------------------------------------------------------------
            | VALUES
            |--------------------------------------------------------------------------
            */

            $outstanding_amount =
                $loan->outstanding_amount ?? 0;

            $overdue_amount =
                $loan->overdue_amount ?? 0;

            /*
            |--------------------------------------------------------------------------
            | OVERDUE DAYS
            |--------------------------------------------------------------------------
            */

            $overdue_days = 0;

            if (
                !empty($loan->due_date)
            ) {

                $due_date =
                    Carbon::parse($loan->due_date);

                if ($due_date->isPast()) {

                    $overdue_days =
                        $due_date->diffInDays(now());

                }

            }

            /*
            |--------------------------------------------------------------------------
            | RISK SCORE
            |--------------------------------------------------------------------------
            */

            $risk_score = 0;

            $risk_score +=
                ($overdue_days * 0.50);

            $risk_score +=
                ($overdue_amount / 1000);

            $risk_score +=
                ($outstanding_amount / 5000);

            /*
            |--------------------------------------------------------------------------
            | PRIORITY SCORE
            |--------------------------------------------------------------------------
            */

            $priority_score =
                round($risk_score, 2);

            /*
            |--------------------------------------------------------------------------
            | PRIORITY LEVEL
            |--------------------------------------------------------------------------
            */

            $priority_level = 'low';

            if ($priority_score >= 80) {

                $priority_level = 'critical';

            } elseif ($priority_score >= 50) {

                $priority_level = 'high';

            } elseif ($priority_score >= 20) {

                $priority_level = 'medium';

            }

            /*
            |--------------------------------------------------------------------------
            | RECOMMENDED ACTION
            |--------------------------------------------------------------------------
            */

            $recommended_action =
                'Routine monitoring';

            if ($priority_level == 'critical') {

                $recommended_action =
                    'Immediate escalation and legal review';

            } elseif ($priority_level == 'high') {

                $recommended_action =
                    'Urgent recovery follow-up required';

            } elseif ($priority_level == 'medium') {

                $recommended_action =
                    'Priority collection attention required';

            }

            /*
            |--------------------------------------------------------------------------
            | CREATE SCORE
            |--------------------------------------------------------------------------
            */

            LoanRecoveryPriorityScore::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $loan->location_id ?? null,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id
                    ?? $loan->contact_id
                    ?? null,

                'score_date' =>
                    now()->toDateString(),

                'overdue_amount' =>
                    $overdue_amount,

                'outstanding_amount' =>
                    $outstanding_amount,

                'overdue_days' =>
                    $overdue_days,

                'risk_score' =>
                    $risk_score,

                'recovery_priority_score' =>
                    $priority_score,

                'priority_level' =>
                    $priority_level,

                'recommended_action' =>
                    $recommended_action,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        return true;
    }
}