<?php

namespace Modules\Loan\Services;

use Modules\Loan\Models\LoanCollectionWorkflow;
use Modules\Loan\Models\RecoveryOfficerProductivity;

class RecoveryOfficerProductivityService
{
    public function generate($business_id)
    {
        /*
        |--------------------------------------------------------------------------
        | CLEAR OLD PRODUCTIVITY
        |--------------------------------------------------------------------------
        */

        RecoveryOfficerProductivity::where(
            'business_id',
            $business_id
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | GET OFFICERS
        |--------------------------------------------------------------------------
        */

        $officer_ids = LoanCollectionWorkflow::where(
            'business_id',
            $business_id
        )
        ->whereNotNull('assigned_to')
        ->distinct()
        ->pluck('assigned_to');

        foreach ($officer_ids as $user_id) {

            /*
            |--------------------------------------------------------------------------
            | WORKFLOWS
            |--------------------------------------------------------------------------
            */

            $workflows =
                LoanCollectionWorkflow::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'assigned_to',
                    $user_id
                )
                ->get();

            /*
            |--------------------------------------------------------------------------
            | COUNTS
            |--------------------------------------------------------------------------
            */

            $assigned_cases =
                $workflows->count();

            $resolved_cases =
                $workflows
                    ->where(
                        'status',
                        'resolved'
                    )
                    ->count();

            $escalation_count =
                $workflows
                    ->whereIn(
                        'escalation_level',
                        [
                            'level_3',
                            'legal'
                        ]
                    )
                    ->count();

            /*
            |--------------------------------------------------------------------------
            | AMOUNTS
            |--------------------------------------------------------------------------
            */

            $total_overdue_amount = 0;
            $recovered_amount = 0;

            foreach ($workflows as $workflow) {

                $loan =
                    $workflow->loan;

                if ($loan) {

                    $total_overdue_amount +=
                        $loan->overdue_amount ?? 0;

                    $recovered_amount +=
                        $loan->recovered_amount ?? 0;

                }

            }

            /*
            |--------------------------------------------------------------------------
            | PTP
            |--------------------------------------------------------------------------
            */

            $ptp_count =
                $workflows
                    ->whereNotNull(
                        'promised_payment_date'
                    )
                    ->count();

            $ptp_kept_count =
                $workflows
                    ->where(
                        'status',
                        'resolved'
                    )
                    ->whereNotNull(
                        'promised_payment_date'
                    )
                    ->count();

            /*
            |--------------------------------------------------------------------------
            | EFFICIENCY %
            |--------------------------------------------------------------------------
            */

            $recovery_efficiency = 0;

            if (
                $total_overdue_amount > 0
            ) {

                $recovery_efficiency =
                    (
                        $recovered_amount
                        /
                        $total_overdue_amount
                    ) * 100;

            }

            /*
            |--------------------------------------------------------------------------
            | PTP SUCCESS %
            |--------------------------------------------------------------------------
            */

            $ptp_success = 0;

            if ($ptp_count > 0) {

                $ptp_success =
                    (
                        $ptp_kept_count
                        /
                        $ptp_count
                    ) * 100;

            }

            /*
            |--------------------------------------------------------------------------
            | PRODUCTIVITY SCORE
            |--------------------------------------------------------------------------
            */

            $productivity_score =
                (
                    ($resolved_cases * 2)
                    +
                    $recovery_efficiency
                    +
                    $ptp_success
                )
                -
                ($escalation_count * 1.5);

            /*
            |--------------------------------------------------------------------------
            | CREATE
            |--------------------------------------------------------------------------
            */

            RecoveryOfficerProductivity::create([

                'business_id' =>
                    $business_id,

                'user_id' =>
                    $user_id,

                'productivity_date' =>
                    now()->toDateString(),

                'assigned_cases' =>
                    $assigned_cases,

                'resolved_cases' =>
                    $resolved_cases,

                'total_overdue_amount' =>
                    $total_overdue_amount,

                'recovered_amount' =>
                    $recovered_amount,

                'ptp_count' =>
                    $ptp_count,

                'ptp_kept_count' =>
                    $ptp_kept_count,

                'escalation_count' =>
                    $escalation_count,

                'recovery_efficiency_percent' =>
                    round(
                        $recovery_efficiency,
                        2
                    ),

                'ptp_success_percent' =>
                    round(
                        $ptp_success,
                        2
                    ),

                'productivity_score' =>
                    round(
                        $productivity_score,
                        2
                    ),

                'created_by' =>
                    auth()->id()

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | RANKINGS
        |--------------------------------------------------------------------------
        */

        $rank = 1;

        $records =
            RecoveryOfficerProductivity::where(
                'business_id',
                $business_id
            )
            ->orderByDesc(
                'productivity_score'
            )
            ->get();

        foreach ($records as $record) {

            $record->ranking_position =
                $rank;

            $record->save();

            $rank++;

        }

        return true;
    }
}