<?php

namespace Modules\BankingMobileBanking\Services;

class MobileBankingReportService
{
    public function registry(): array
    {
        return [
            'Mobile Registration Report',
            'Device Binding Report',
            'Mobile Transfer Report',
            'QR Payment Report',
            'Bill Payment Report',
            'Push Notification Delivery Report',
            'Login & Security Event Report',
        ];
    }
}
