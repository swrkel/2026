<?php

namespace Modules\Loan\Services;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanCollectionWorkflow;

class LoanCollectionWorkflowService
{
    public function generate($business_id)
    {
        /*
        |--------------------------------------------------------------------------
        | CLEAR OPEN WORKFLOWS
        |--------------------------------------------------------------------------
        */

        LoanCollectionWorkflow::where(
            'business_id',
            $business_id
        )->whereIn(
            'status',
            ['open', 'followup']
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | GET OVERDUE LOANS
        |--------------------------------------------------------------------------
        */

        $loans = Loan::where(
            'business_id',
            $business_id
        )
        ->where(function ($q) {

            $q->where('status', 'overdue');

            if (
                \Schema::hasColumn(
                    'loans',
                    'is_overdue'
                )
            ) {

                $q->orWhere(
                    'is_overdue',
                    1
                );

            }

        })
        ->get();

        foreach ($loans as $loan) {

            /*
            |--------------------------------------------------------------------------
            | PRIORITY LEVEL
            |--------------------------------------------------------------------------
            */

            $priority_level = 'medium';

            $outstanding =
                $loan->outstanding_amount ?? 0;

            if ($outstanding >= 500000) {

                $priority_level = 'critical';

            } elseif ($outstanding >= 100000) {

                $priority_level = 'high';

            }

            /*
            |--------------------------------------------------------------------------
            | ESCALATION LEVEL
            |--------------------------------------------------------------------------
            */

            $escalation_level = 'level_1';

            if ($priority_level == 'critical') {

                $escalation_level = 'legal';

            } elseif ($priority_level == 'high') {

                $escalation_level = 'level_3';

            }

            /*
            |--------------------------------------------------------------------------
            | WORKFLOW STAGE
            |--------------------------------------------------------------------------
            */

            $workflow_stage =
                'Collection Follow-up';

            if ($priority_level == 'critical') {

                $workflow_stage =
                    'Legal Escalation Review';

            }

            /*
            |--------------------------------------------------------------------------
            | CREATE WORKFLOW
            |--------------------------------------------------------------------------
            */

            LoanCollectionWorkflow::create([

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

                'workflow_no' =>
                    'COL-' . strtoupper(uniqid()),

                'priority_level' =>
                    $priority_level,

                'escalation_level' =>
                    $escalation_level,

                'workflow_stage' =>
                    $workflow_stage,

                'followup_date' =>
                    now()->addDays(2),

                'last_contact_date' =>
                    now(),

                'next_action' =>
                    'Customer follow-up and recovery engagement required.',

                'remarks' =>
                    'System-generated collection workflow.',

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        return true;
    }
}