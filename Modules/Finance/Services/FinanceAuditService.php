<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\FinanceAuditLog;

class FinanceAuditService
{
    public static function log(
        $module,
        $action,
        $description = null,
        $reference_type = null,
        $reference_id = null,
        $old_values = null,
        $new_values = null,
        $location_id = null
    ) {
        return FinanceAuditLog::create([
            'business_id' => session()->get('user.business_id'),
            'location_id' => $location_id,
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'description' => $description,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}