<?php

namespace Modules\BankingPaymentsHub\Services;

class PaymentsRegistryService
{
    public function items(): array
    {
        return [
            ['code' => 'INTERNAL', 'name' => 'Internal transfer/payment processing', 'status' => 'Ready for UI testing'],
            ['code' => 'RTGS', 'name' => 'RTGS/NEFT/ACH/SWIFT configurable rail', 'status' => 'Ready for UI testing'],
            ['code' => 'QUEUE', 'name' => 'Payment queue, retry and exception handling', 'status' => 'Ready for UI testing'],
            ['code' => 'RECON', 'name' => 'Payment reconciliation workflow', 'status' => 'Ready for UI testing'],
        ];
    }
    public function reports(): array
    {
        return ['Payment Register', 'Payment Queue', 'Failed Payments', 'Batch Payments', 'Reconciliation Report', 'Payment Monitoring'];
    }
    public function settings(): array
    {
        return ['Payment rails', 'Routing rules', 'Approval limits', 'Retry rules', 'Cut-off times', 'Number series'];
    }
}
