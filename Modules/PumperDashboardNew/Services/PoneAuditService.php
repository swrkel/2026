<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\PumperDashboardNew\Entities\PoneAuditLog;

class PoneAuditService
{
    public function log(
        string $action,
        string $entityType,
        ?int $entityId,
        $before = null,
        $after = null,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $operatorProfileId = null,
        ?int $userId = null
    ): void {
        try {
            $session = request()->hasSession() ? request()->session() : null;
            PoneAuditLog::query()->create([
                'business_id' => $businessId ?? (int) optional($session)->get('pone.business_id', optional($session)->get('business.id', 0)),
                'location_id' => $locationId ?? optional($session)->get('pone.location_id'),
                'operator_profile_id' => $operatorProfileId ?? optional($session)->get('pone.operator_profile_id'),
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'before_data' => $this->normalise($before),
                'after_data' => $this->normalise($after),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 2000),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function normalise($value): ?array
    {
        if ($value === null) return null;
        if ($value instanceof Model) return $value->toArray();
        if (is_array($value)) return $value;
        if (is_object($value)) return (array) $value;
        return ['value' => $value];
    }
}
