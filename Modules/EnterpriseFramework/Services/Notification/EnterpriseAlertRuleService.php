<?php

namespace Modules\EnterpriseFramework\Services\Notification;

class EnterpriseAlertRuleService
{
    public function defaultRules(): array
    {
        return [
            ['key' => 'low_cash', 'label' => 'Low Cash Balance', 'severity' => 'warning'],
            ['key' => 'negative_bank', 'label' => 'Negative Bank Balance', 'severity' => 'danger'],
            ['key' => 'budget_exceeded', 'label' => 'Budget Exceeded', 'severity' => 'warning'],
            ['key' => 'overdue_receivables', 'label' => 'Overdue Receivables', 'severity' => 'warning'],
            ['key' => 'missing_approval', 'label' => 'Missing Approval', 'severity' => 'info'],
        ];
    }

    public function evaluate(array $metrics = []): array
    {
        $alerts = [];
        if (($metrics['cash_balance'] ?? 0) < ($metrics['cash_threshold'] ?? 0)) {
            $alerts[] = ['key' => 'low_cash', 'message' => 'Cash balance is below configured threshold.', 'severity' => 'warning'];
        }
        if (($metrics['bank_balance'] ?? 0) < 0) {
            $alerts[] = ['key' => 'negative_bank', 'message' => 'Bank balance is negative.', 'severity' => 'danger'];
        }
        return $alerts;
    }
}
