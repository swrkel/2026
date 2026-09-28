<?php

namespace Modules\HRManager\Services;

use Modules\HRManager\Models\HrEmployeeRecordAuditLog;

class HrEmployeeRecordService
{
    public function audit(array $data): void
    {
        HrEmployeeRecordAuditLog::create([
            'business_id' => $data['business_id'],
            'employee_id' => $data['employee_id'] ?? null,
            'record_type' => $data['record_type'],
            'record_id' => $data['record_id'] ?? null,
            'action' => $data['action'],
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? null,
            'note' => $data['note'] ?? null,
            'action_by' => $data['user_id'] ?? null,
            'action_at' => now(),
        ]);
    }
}
