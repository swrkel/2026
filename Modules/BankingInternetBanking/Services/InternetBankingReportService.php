<?php

namespace Modules\BankingInternetBanking\Services;

class InternetBankingReportService
{
    public function reports(): array
    {
        return [
            'login_audit' => 'Login Audit',
            'transfer_history' => 'Transfer History',
            'bill_payment_history' => 'Bill Payment History',
            'beneficiary_register' => 'Beneficiary Register',
            'device_register' => 'Device Register',
            'security_events' => 'Security Event Report',
            'customer_activity' => 'Customer Activity Report',
        ];
    }
}
