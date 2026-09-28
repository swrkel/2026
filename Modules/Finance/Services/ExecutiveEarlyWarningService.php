<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Schema;

use Modules\Finance\Entities\ExecutiveEarlyWarning;
use Modules\Loan\Models\Loan;

class ExecutiveEarlyWarningService
{
    public function generate($business_id)
    {
        /*
        |--------------------------------------------------------------------------
        | CLEAR OLD OPEN WARNINGS
        |--------------------------------------------------------------------------
        */

        ExecutiveEarlyWarning::where(
            'business_id',
            $business_id
        )
        ->where(
            'status',
            'open'
        )
        ->delete();

        /*
        |--------------------------------------------------------------------------
        | CHECK LOANS TABLE EXISTS
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('loans')) {

            ExecutiveEarlyWarning::create([

                'business_id' => $business_id,

                'warning_no' =>
                    'EWS-' . strtoupper(uniqid()),

                'warning_type' =>
                    'system',

                'severity' =>
                    'medium',

                'module' =>
                    'Finance',

                'subject' =>
                    'Loans Table Missing',

                'description' =>
                    'Loans table not found for warning generation.',

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | HIGH OVERDUE LOANS
        |--------------------------------------------------------------------------
        */

        $high_overdue_loans = Loan::where(
            'business_id',
            $business_id
        );

        if (
            Schema::hasColumn(
                'loans',
                'status'
            )
        ) {

            $high_overdue_loans->where(function ($q) {

                $q->where(
                    'status',
                    'overdue'
                );

                if (
                    Schema::hasColumn(
                        'loans',
                        'is_overdue'
                    )
                ) {

                    $q->orWhere(
                        'is_overdue',
                        1
                    );

                }

            });

        }

        $high_overdue_loans =
            $high_overdue_loans->get();

        foreach ($high_overdue_loans as $loan) {

            ExecutiveEarlyWarning::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $loan->location_id ?? null,

                'warning_no' =>
                    'EWS-' . strtoupper(uniqid()),

                'warning_type' =>
                    'loan_overdue',

                'severity' =>
                    'high',

                'module' =>
                    'Loan',

                'subject' =>
                    'High Overdue Loan Exposure',

                'description' =>
                    'Loan account has entered overdue risk category.',

                'reference_type' =>
                    'loan',

                'reference_id' =>
                    $loan->id,

                'amount' =>
                    $loan->outstanding_amount ?? 0,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | LARGE EXPOSURE WARNINGS
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'loans',
                'outstanding_amount'
            )
        ) {

            $critical_loans = Loan::where(
                'business_id',
                $business_id
            )
            ->where(
                'outstanding_amount',
                '>=',
                100000
            )
            ->get();

            foreach ($critical_loans as $loan) {

                ExecutiveEarlyWarning::create([

                    'business_id' =>
                        $business_id,

                    'location_id' =>
                        $loan->location_id ?? null,

                    'warning_no' =>
                        'EWS-' . strtoupper(uniqid()),

                    'warning_type' =>
                        'critical_exposure',

                    'severity' =>
                        'critical',

                    'module' =>
                        'Loan',

                    'subject' =>
                        'Critical Portfolio Exposure',

                    'description' =>
                        'Large outstanding exposure detected in loan portfolio.',

                    'reference_type' =>
                        'loan',

                    'reference_id' =>
                        $loan->id,

                    'amount' =>
                        $loan->outstanding_amount ?? 0,

                    'status' =>
                        'open',

                    'created_by' =>
                        auth()->id()

                ]);

            }

        }

        /*
        |--------------------------------------------------------------------------
        | GENERATE TEST WARNING IF EMPTY
        |--------------------------------------------------------------------------
        */

        $total_warnings =
            ExecutiveEarlyWarning::where(
                'business_id',
                $business_id
            )->count();

        if ($total_warnings == 0) {

            ExecutiveEarlyWarning::create([

                'business_id' =>
                    $business_id,

                'warning_no' =>
                    'EWS-' . strtoupper(uniqid()),

                'warning_type' =>
                    'system',

                'severity' =>
                    'low',

                'module' =>
                    'Finance',

                'subject' =>
                    'System Test Warning',

                'description' =>
                    'Executive Early Warning Engine operational successfully.',

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        return true;
    }
}