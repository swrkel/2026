<?php

namespace Modules\StockTakingNew\Services;

use Modules\StockTakingNew\Entities\StockTakeAuditLog;

class AuditService
{
    public function log(
        ?int $businessId,
        ?int $sessionId,
        string $event,
        string $entityType = 'session',
        ?int $entityId = null,
        array $old = [],
        array $new = [],
        array $metadata = [],
        ?int $actorId = null
    ): void {
        $request = app()->runningInConsole() ? null : request();
        StockTakeAuditLog::create([
            'business_id' => $businessId,
            'session_id' => $sessionId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : 'console',
            'created_by' => $actorId ?? auth()->id(),
        ]);
    }
}
