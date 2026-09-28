<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\PetroPDNew\Entities\PdnewAuditLog;
use RuntimeException;
use Throwable;

class PdnewAuditService
{
    public function __construct(private PdnewContextService $context) {}

    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        mixed $oldValues = null,
        mixed $newValues = null,
        ?int $businessId = null,
        ?int $locationId = null,
        ?int $userId = null
    ): PdnewAuditLog {
        $old = $this->normalise($oldValues);
        $new = $this->normalise($newValues);

        $businessId = $businessId
            ?: $this->integerFrom($new, 'business_id')
            ?: $this->integerFrom($old, 'business_id')
            ?: $this->contextValue('businessId');

        if ($businessId <= 0) {
            throw new RuntimeException(
                'A business context is required to record a Petro PD-New audit event.'
            );
        }

        $locationId = $locationId
            ?: $this->integerFrom($new, 'location_id')
            ?: $this->integerFrom($old, 'location_id')
            ?: $this->contextValue('locationId');

        $userId = $userId ?: $this->contextValue('userId');

        $ipAddress = null;
        $userAgent = null;
        if (! app()->runningInConsole()) {
            $ipAddress = request()->ip();
            $userAgent = substr((string) request()->userAgent(), 0, 65000);
        }

        return PdnewAuditLog::query()->create([
            'business_id' => $businessId,
            'location_id' => $locationId ?: null,
            'user_id' => $userId ?: null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    private function normalise(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->getAttributes();
        }

        if ($value instanceof \JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return $value;
    }

    private function integerFrom(mixed $value, string $key): int
    {
        return is_array($value) ? (int) ($value[$key] ?? 0) : 0;
    }

    private function contextValue(string $method): int
    {
        try {
            return (int) $this->context->{$method}();
        } catch (Throwable) {
            return 0;
        }
    }
}
