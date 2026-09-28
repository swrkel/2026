<?php

namespace Modules\PriceChangeNew\Services;

use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Entities\PriceChangeAudit;

class PriceChangeAuditService
{
    /** @param array<string, mixed> $payload */
    public function record(
        PriceChange $change,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        array $payload = [],
        ?int $userId = null
    ): PriceChangeAudit {
        $request = app()->bound('request') ? request() : null;

        return PriceChangeAudit::query()->create([
            'price_change_id' => $change->id,
            'business_id' => $change->business_id,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'payload' => $payload,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : 'console',
            'created_at' => now(),
        ]);
    }
}
