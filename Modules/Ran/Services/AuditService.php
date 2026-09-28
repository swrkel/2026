<?php

namespace Modules\Ran\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Ran\Entities\AuditLog;
use Modules\Ran\Support\RanContext;

class AuditService
{
    public function record(string $action, Model|string $entity, ?int $entityId = null, array $old = [], array $new = []): AuditLog
    {
        if ($entity instanceof Model) {
            $entityId = (int) $entity->getKey();
            $new = $new ?: $entity->getAttributes();
            $entity = get_class($entity);
        }
        return AuditLog::create([
            'business_id' => RanContext::businessId(),
            'location_id' => RanContext::locationId(),
            'store_id' => RanContext::storeId(),
            'user_id' => RanContext::userId(),
            'action' => $action,
            'entity_type' => (string) $entity,
            'entity_id' => $entityId,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
