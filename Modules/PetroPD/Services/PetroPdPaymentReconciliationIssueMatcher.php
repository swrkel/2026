<?php

namespace Modules\PetroPD\Services;

class PetroPdPaymentReconciliationIssueMatcher
{
    public function matchingIssues(array $issues, string $issueType, ?int $pumpPaymentId = null): array
    {
        $pumpPaymentId = (int) ($pumpPaymentId ?? 0);

        return array_values(array_filter($issues, function ($issue) use ($issueType, $pumpPaymentId) {
            if (! is_array($issue) || (string) ($issue['type'] ?? '') !== $issueType) {
                return false;
            }

            if ($pumpPaymentId <= 0) {
                return true;
            }

            return (int) ($issue['pump_payment_id'] ?? 0) === $pumpPaymentId;
        }));
    }

    public function isResolved(array $issues, string $issueType, ?int $pumpPaymentId = null): bool
    {
        return $this->matchingIssues($issues, $issueType, $pumpPaymentId) === [];
    }
}
