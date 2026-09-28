<?php

namespace Modules\PumperDashboardNew\Services\Integration;

use Modules\PumperDashboardNew\Entities\PoneIntegrationOutbox;

class PoneOutboxService
{
    public function queue(
        int $businessId,
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        array $payload = [],
        ?string $error = null
    ): PoneIntegrationOutbox {
        $key = hash('sha256', implode('|', [$businessId, $aggregateType, $aggregateId, $eventType]));

        return PoneIntegrationOutbox::query()->updateOrCreate(
            ['idempotency_key' => $key],
            [
                'business_id' => $businessId,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => 'pending',
                'available_at' => now(),
                'last_error' => $error,
                'processed_at' => null,
            ]
        );
    }
}
