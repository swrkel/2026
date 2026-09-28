<?php

namespace Modules\PetroDirectNew\Services;

use Modules\PetroDirectNew\Entities\PdirectnewAuditLog;
use Modules\PetroDirectNew\Support\BusinessContext;

class AuditService
{
    public function __construct(private BusinessContext $context) {}

    public function record(string $action, string $entityType, ?int $entityId, array $before = [], array $after = []): void
    {
        try {
            PdirectnewAuditLog::create([
                'business_id' => $this->context->businessId(),
                'location_id' => $after['location_id'] ?? $before['location_id'] ?? null,
                'user_id' => $this->context->userId(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'before_data' => json_encode($before),
                'after_data' => json_encode($after),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
