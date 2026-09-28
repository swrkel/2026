<?php
namespace Modules\POS\Services;

class POSEnterpriseMonitorService
{
    public function summary(): array
    {
        return [
            'open_registers' => 0,
            'active_sessions' => 0,
            'today_sales' => '0.0000',
            'pending_print_jobs' => 0,
            'offline_devices' => 0,
        ];
    }
}
