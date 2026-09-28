<?php

namespace Modules\BankingAML\Services;

class BankingAMLDashboardService
{
    public function cards(): array
    {
        return [
            ['label' => 'Open Items', 'value' => 0],
            ['label' => 'Pending Approval', 'value' => 0],
            ['label' => 'Reports', 'value' => 8],
            ['label' => 'Alerts', 'value' => 0],
        ];
    }
}
