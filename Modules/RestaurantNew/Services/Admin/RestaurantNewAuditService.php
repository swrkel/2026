<?php

namespace Modules\RestaurantNew\Services\Admin;

use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\RestaurantNewAuditLog;

class RestaurantNewAuditService
{
    public function record(int $businessId, ?int $locationId, ?int $userId, string $area, string $action, ?string $entityType = null, ?int $entityId = null, array $old = [], array $new = [], ?Request $request = null): RestaurantNewAuditLog
    {
        $request = $request ?: request();
        return RestaurantNewAuditLog::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'user_id' => $userId,
            'module_area' => $area,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }
}
