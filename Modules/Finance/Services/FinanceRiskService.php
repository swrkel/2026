<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\FinanceEscalation;
use Modules\Finance\Entities\FinanceRiskAlert;

class FinanceRiskService
{
    public static function monitorLargeTransaction(
        $business_id,
        $location_id = null,
        $amount = 0,
        $module = null,
        $subject = null,
        $reference_type = null,
        $reference_id = null
    ) {
        $risk_level = null;

        if ($amount >= 5000000) {
            $risk_level = 'critical';
        } elseif ($amount >= 2000000) {
            $risk_level = 'high';
        } elseif ($amount >= 1000000) {
            $risk_level = 'medium';
        }

        if (empty($risk_level)) {
            return null;
        }

        $risk_alert = FinanceRiskAlert::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'alert_no' => 'RISK-' . time(),
            'alert_type' => 'Large Transaction',
            'risk_level' => $risk_level,
            'module' => $module,
            'subject' => $subject,
            'description' => 'Large transaction detected in ' . $module,
            'amount' => $amount,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        FinanceNotificationService::create(
            'Risk Alert',
            'Large Transaction Detected',
            'A large transaction of ' . number_format($amount, 2) . ' was detected.',
            null,
            $risk_level,
            'finance_risk_alerts',
            $risk_alert->id,
            $location_id
        );

        FinanceAuditService::log(
            'Finance Risk',
            'Risk Alert Created',
            'Large transaction risk alert created.',
            'finance_risk_alerts',
            $risk_alert->id,
            null,
            $risk_alert->toArray(),
            $location_id
        );

        if ($risk_level == 'critical') {
            $escalation = FinanceEscalation::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'escalation_no' => 'RISK-ESC-' . time(),
                'module' => 'Finance Risk',
                'escalation_type' => 'Critical Risk Alert',
                'severity' => 'critical',
                'subject' => 'Critical Financial Risk Detected',
                'description' => 'Critical transaction risk detected.',
                'reference_type' => 'finance_risk_alerts',
                'reference_id' => $risk_alert->id,
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);

            FinanceNotificationService::create(
                'Critical Risk Escalation',
                'Critical Financial Risk',
                'Critical financial risk escalation created.',
                null,
                'critical',
                'finance_escalations',
                $escalation->id,
                $location_id
            );
        }

        return $risk_alert;
    }

    public static function detectDuplicatePayment(
        $business_id,
        $transaction_id = null,
        $amount = 0,
        $payment_ref_no = null,
        $module = null,
        $reference_type = null,
        $reference_id = null,
        $location_id = null
    ) {
        if (empty($business_id) || empty($transaction_id) || empty($amount)) {
            return null;
        }

        if (!DB::getSchemaBuilder()->hasTable('transaction_payments')) {
            return null;
        }

        $query = DB::table('transaction_payments')
            ->where('transaction_id', $transaction_id)
            ->where('amount', $amount);

        if (!empty($payment_ref_no)) {
            $query->where('payment_ref_no', $payment_ref_no);
        }

        if (!empty($reference_id)) {
            $query->where('id', '!=', $reference_id);
        }

        $duplicate_count = $query->count();

        if ($duplicate_count <= 0) {
            return null;
        }

        $risk_alert = FinanceRiskAlert::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'alert_no' => 'DUP-' . time(),
            'alert_type' => 'Duplicate Payment Detection',
            'risk_level' => 'high',
            'module' => $module ?? 'Payments',
            'subject' => 'Possible Duplicate Payment',
            'description' => 'Potential duplicate payment detected.',
            'amount' => $amount,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        FinanceNotificationService::create(
            'Duplicate Payment Alert',
            'Possible Duplicate Payment',
            'Potential duplicate payment detected.',
            null,
            'high',
            'finance_risk_alerts',
            $risk_alert->id,
            $location_id
        );

        FinanceAuditService::log(
            'Finance Risk',
            'Duplicate Payment Detected',
            'Possible duplicate payment detected.',
            'finance_risk_alerts',
            $risk_alert->id,
            null,
            $risk_alert->toArray(),
            $location_id
        );

        return $risk_alert;
    }
}