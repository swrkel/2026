<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\EnterpriseNotification;
use Modules\Finance\Entities\ExecutiveEarlyWarning;

use Modules\Loan\Models\LoanCollectionWorkflow;
use Modules\Loan\Models\LoanRecoveryPriorityScore;

class EnterpriseNotificationService
{
    public function generate($business_id)
    {
        /*
        |--------------------------------------------------------------------------
        | CLEAR OPEN NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        EnterpriseNotification::where(
            'business_id',
            $business_id
        )->where(
            'status',
            'open'
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | EXECUTIVE WARNINGS
        |--------------------------------------------------------------------------
        */

        $warnings = ExecutiveEarlyWarning::where(
            'business_id',
            $business_id
        )
        ->where(
            'status',
            'open'
        )
        ->get();

        foreach ($warnings as $warning) {

            EnterpriseNotification::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $warning->location_id,

                'notification_no' =>
                    'NOT-' . strtoupper(uniqid()),

                'notification_type' =>
                    'executive_warning',

                'module' =>
                    'Finance',

                'severity' =>
                    $warning->severity == 'critical'
                        ? 'critical'
                        : 'warning',

                'title' =>
                    'Executive Warning Alert',

                'message' =>
                    $warning->subject,

                'target_type' =>
                    'warning',

                'target_id' =>
                    $warning->id,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | RECOVERY PRIORITIES
        |--------------------------------------------------------------------------
        */

        $priorities =
            LoanRecoveryPriorityScore::where(
                'business_id',
                $business_id
            )
            ->whereIn(
                'priority_level',
                ['high', 'critical']
            )
            ->get();

        foreach ($priorities as $priority) {

            EnterpriseNotification::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $priority->location_id,

                'notification_no' =>
                    'NOT-' . strtoupper(uniqid()),

                'notification_type' =>
                    'recovery_priority',

                'module' =>
                    'Loan',

                'severity' =>
                    $priority->priority_level == 'critical'
                        ? 'critical'
                        : 'warning',

                'title' =>
                    'High Recovery Priority',

                'message' =>
                    $priority->recommended_action,

                'target_type' =>
                    'recovery_priority',

                'target_id' =>
                    $priority->id,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | COLLECTION WORKFLOWS
        |--------------------------------------------------------------------------
        */

        $workflows =
            LoanCollectionWorkflow::where(
                'business_id',
                $business_id
            )
            ->whereIn(
                'status',
                ['open', 'followup']
            )
            ->get();

        foreach ($workflows as $workflow) {

            EnterpriseNotification::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $workflow->location_id,

                'notification_no' =>
                    'NOT-' . strtoupper(uniqid()),

                'notification_type' =>
                    'collection_workflow',

                'module' =>
                    'Loan',

                'severity' =>
                    $workflow->priority_level == 'critical'
                        ? 'critical'
                        : 'info',

                'title' =>
                    'Collection Workflow Reminder',

                'message' =>
                    $workflow->next_action,

                'target_type' =>
                    'collection_workflow',

                'target_id' =>
                    $workflow->id,

                'assigned_to' =>
                    $workflow->assigned_to,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id()

            ]);

        }

        return true;
    }
}