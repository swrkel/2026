<?php

namespace Modules\EnterpriseFramework\Services\Scheduler;

class ReportSchedulerService
{
    public function all(): array
    {
        return [
            ['name' => 'Daily Cash Position', 'frequency' => 'Daily', 'format' => 'PDF', 'status' => 'Template'],
            ['name' => 'Monthly Board Pack', 'frequency' => 'Monthly', 'format' => 'PDF', 'status' => 'Template'],
        ];
    }
}
