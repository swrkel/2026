<?php
namespace Modules\DistributionNew\Services;

use Modules\DistributionNew\Models\DisnewAuditTrail;

class DisnewAuditService
{
    public function log(int $businessId, string $action, string $entityType, $entityId = null, array $before = [], array $after = [], ?int $userId = null, ?int $locationId = null): void
    {
        DisnewAuditTrail::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_json' => json_encode($before),
            'after_json' => json_encode($after),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string)request()->userAgent(), 0, 500),
        ]);
    }
}
