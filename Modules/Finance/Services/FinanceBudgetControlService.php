<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\FinanceBudget;
use Modules\Finance\Entities\FinanceEscalation;

class FinanceBudgetControlService
{
    public static function checkBudgetUsage(
        $business_id,
        $location_id = null,
        $account_id = null,
        $amount = 0,
        $reference_type = null,
        $reference_id = null
    ) {
        $budget = FinanceBudget::where('business_id', $business_id)
            ->where('status', 'active')
            ->when($location_id, function ($query) use ($location_id) {
                $query->where(function ($q) use ($location_id) {
                    $q->where('location_id', $location_id)
                        ->orWhereNull('location_id');
                });
            })
            ->when($account_id, function ($query) use ($account_id) {
                $query->where(function ($q) use ($account_id) {
                    $q->where('account_id', $account_id)
                        ->orWhereNull('account_id');
                });
            })
            ->first();

        if (empty($budget)) {
            return null;
        }

        $new_utilized_amount =
            $budget->utilized_amount + $amount;

        $remaining_amount =
            $budget->allocated_amount - $new_utilized_amount;

        $utilization_percentage = 0;

        if ($budget->allocated_amount > 0) {
            $utilization_percentage =
                ($new_utilized_amount / $budget->allocated_amount) * 100;
        }

        $budget->utilized_amount = $new_utilized_amount;
        $budget->remaining_amount = $remaining_amount;
        $budget->save();

        FinanceAuditService::log(
            'Finance Budget Control',
            'Budget Usage Updated',
            'Budget utilization updated for: ' . $budget->budget_name,
            'finance_budgets',
            $budget->id,
            null,
            $budget->toArray(),
            $budget->location_id
        );

        if ($utilization_percentage >= $budget->alert_threshold) {
            FinanceNotificationService::create(
                'Budget Alert',
                'Budget Threshold Reached',
                'Budget ' . $budget->budget_name . ' reached ' . round($utilization_percentage, 2) . '% utilization.',
                null,
                'high',
                $reference_type,
                $reference_id,
                $budget->location_id
            );
        }

        if ($remaining_amount < 0) {
            $escalation = FinanceEscalation::create([
                'business_id' => $business_id,
                'location_id' => $budget->location_id,
                'escalation_no' => 'BUD-ESC-' . time(),
                'module' => 'Finance Budget',
                'escalation_type' => 'Budget Overspending',
                'severity' => 'critical',
                'subject' => 'Budget Overspending Detected',
                'description' => 'Budget ' . $budget->budget_name . ' has exceeded the allocated amount.',
                'reference_type' => $reference_type,
                'reference_id' => $reference_id,
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            FinanceNotificationService::create(
                'Budget Overspending',
                'Critical Budget Overspending',
                'Budget ' . $budget->budget_name . ' has exceeded the allocated amount.',
                null,
                'critical',
                'finance_escalations',
                $escalation->id,
                $budget->location_id
            );

            FinanceAuditService::log(
                'Finance Budget Control',
                'Overspending Escalation Created',
                'Budget overspending escalation created for: ' . $budget->budget_name,
                'finance_escalations',
                $escalation->id,
                null,
                $escalation->toArray(),
                $budget->location_id
            );
        }

        return $budget;
    }
}