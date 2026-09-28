<?php

namespace Modules\FinanceReports\Services\Finalization;

class FinanceReportSchedulerService
{
    public function defaultSchedules(): array
    {
        return [
            ['report' => 'Daily Cash Position', 'frequency' => 'Daily', 'time' => 'End of day', 'format' => 'PDF / Excel', 'status' => 'Ready for configuration'],
            ['report' => 'Weekly Cash Flow', 'frequency' => 'Weekly', 'time' => 'Week close', 'format' => 'PDF', 'status' => 'Ready for configuration'],
            ['report' => 'Monthly Profit & Loss', 'frequency' => 'Monthly', 'time' => 'Month close', 'format' => 'PDF / Excel', 'status' => 'Ready for configuration'],
            ['report' => 'Monthly Trial Balance', 'frequency' => 'Monthly', 'time' => 'Month close', 'format' => 'Excel', 'status' => 'Ready for configuration'],
            ['report' => 'Management Financial Pack', 'frequency' => 'Monthly', 'time' => 'Month close', 'format' => 'PDF Pack', 'status' => 'Ready for configuration'],
        ];
    }
}
