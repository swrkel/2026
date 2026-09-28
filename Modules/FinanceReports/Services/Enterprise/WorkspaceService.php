<?php

namespace Modules\FinanceReports\Services\Enterprise;

class WorkspaceService
{
    public function workspace(): array
    {
        return [
            'recent' => ['Trial Balance - New', 'Profit & Loss - New', 'Cash Position Report - New', 'Financial Intelligence - New'],
            'favourites' => ['Board Pack - New', 'CFO Dashboard - New', 'Branch Performance - New'],
            'quick_filters' => ['Today', 'This Month', 'This Financial Year', 'Consolidated', 'Current Branch'],
            'shortcuts' => ['Export Board Pack', 'Run Financial Health Score', 'Open Report Builder', 'Open Scheduled Reports'],
        ];
    }
}
