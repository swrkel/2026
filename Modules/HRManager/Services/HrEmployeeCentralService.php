<?php
namespace Modules\HRManager\Services;

use Modules\HRManager\Models\HrEmployeeActivityLog;

class HrEmployeeCentralService
{
    public function log($businessId, $employeeId, $type, $title, $description = null, $userId = null): void
    {
        HrEmployeeActivityLog::create([
            'business_id' => $businessId,
            'employee_id' => $employeeId,
            'activity_type' => $type,
            'activity_title' => $title,
            'activity_description' => $description,
            'created_by' => $userId,
        ]);
    }
}
