<?php

namespace Modules\Loan\Services;

use Modules\Loan\Models\LoanRecoveryEscalation;

class RecoveryMonitoringService
{
    /**
     * Get critical risk escalations.
     */
    public function getCriticalRiskEscalations(
        $business_id
    ) {

        return LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->complianceRisk()
            ->latest()
            ->get();
    }

    /**
     * Get aged escalations.
     */
    public function getAgedEscalations(
        $business_id,
        $days = 30
    ) {

        return LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->aged($days)
            ->latest()
            ->get();
    }

    /**
     * Get unresolved escalations.
     */
    public function getUnresolvedEscalations(
        $business_id
    ) {

        return LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->unresolved()
            ->latest()
            ->get();
    }

    /**
     * Get high priority escalations.
     */
    public function getHighPriorityEscalations(
        $business_id
    ) {

        return LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->highPriority()
            ->latest()
            ->get();
    }

    /**
     * Governance exception alerts.
     */
    public function getGovernanceAlerts(
        $business_id
    ) {

        return LoanRecoveryEscalation::where(
                'business_id',
                $business_id
            )
            ->where(
                'priority_level',
                'critical'
            )
            ->whereNull(
                'resolution_date'
            )
            ->latest()
            ->get();
    }
}