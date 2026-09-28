<?php

namespace Modules\BankingCRM\Services;

class BankingCRMDashboardService
{
    public function cards(): array
    {
        return [
            ['label' => 'Open Items', 'value' => 0],
            ['label' => 'Pending Approval', 'value' => 0],
            ['label' => 'Reports', 'value' => 9],
            ['label' => 'Alerts', 'value' => 0],
        ];
    }
}
