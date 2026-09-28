<?php

namespace Modules\BankingMobileBanking\Services;

class MobileBankingDashboardService
{
    public function summary(): array
    {
        return [
            'registered_customers' => 0,
            'active_devices' => 0,
            'today_transfers' => 0,
            'pending_qr_payments' => 0,
            'failed_notifications' => 0,
        ];
    }
}
