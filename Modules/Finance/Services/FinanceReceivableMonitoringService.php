<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\Transaction;
use Carbon\Carbon;
use Modules\Finance\Entities\FinanceRiskAlert;

class FinanceReceivableMonitoringService
{
    public static function monitor()
    {
        $overdues = Transaction::where('type', 'sell')
            ->where('payment_status', '!=', 'paid')
            ->whereDate('transaction_date', '<=', Carbon::now()->subDays(30))
            ->get();

        foreach ($overdues as $transaction) {

            $existing = FinanceRiskAlert::where(
                'reference_type',
                'transactions'
            )
            ->where(
                'reference_id',
                $transaction->id
            )
            ->where(
                'alert_type',
                'Overdue Receivable'
            )
            ->first();

            if (!empty($existing)) {
                continue;
            }

            $risk_level = 'medium';

            $days = Carbon::parse(
                $transaction->transaction_date
            )->diffInDays(now());

            if ($days >= 90) {
                $risk_level = 'critical';
            } elseif ($days >= 60) {
                $risk_level = 'high';
            }

            $alert = FinanceRiskAlert::create([

                'business_id' =>
                    $transaction->business_id,

                'location_id' =>
                    $transaction->location_id,

                'alert_no' =>
                    'AR-' . time() . rand(100,999),

                'alert_type' =>
                    'Overdue Receivable',

                'risk_level' =>
                    $risk_level,

                'module' =>
                    'Accounts Receivable',

                'subject' =>
                    'Overdue Customer Balance',

                'description' =>
                    'Customer invoice overdue for '
                    . $days . ' days.',

                'amount' =>
                    $transaction->final_total,

                'reference_type' =>
                    'transactions',

                'reference_id' =>
                    $transaction->id,

                'status' =>
                    'open',

                'created_by' =>
                    1,
            ]);

            FinanceNotificationService::create(
                'Overdue Receivable',
                'Customer Invoice Overdue',
                'Invoice overdue for '
                . $days . ' days.',
                null,
                $risk_level,
                'finance_risk_alerts',
                $alert->id,
                $transaction->location_id
            );

            FinanceAuditService::log(
                'Finance Receivable Monitoring',
                'Overdue Invoice Detected',
                'Overdue customer invoice detected.',
                'finance_risk_alerts',
                $alert->id,
                null,
                $alert->toArray(),
                $transaction->location_id
            );
        }

        return true;
    }
}