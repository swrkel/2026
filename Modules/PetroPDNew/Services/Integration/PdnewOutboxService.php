<?php

namespace Modules\PetroPDNew\Services\Integration;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewIntegrationOutbox;
use RuntimeException;

class PdnewOutboxService
{
    public function queue(
        int $businessId,
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        array $payload = []
    ): PdnewIntegrationOutbox {
        $aggregateType = trim($aggregateType);
        $eventType = trim($eventType);

        if ($businessId <= 0 || $aggregateId <= 0 || $aggregateType === '' || $eventType === '') {
            throw new RuntimeException('A valid business, aggregate and event are required for the Petro PD-New outbox.');
        }

        $key = hash('sha256', implode('|', [$businessId, $aggregateType, $aggregateId, $eventType]));

        return DB::transaction(function () use (
            $businessId,
            $aggregateType,
            $aggregateId,
            $eventType,
            $payload,
            $key
        ): PdnewIntegrationOutbox {
            $now = now();
            DB::table('pdnew_integration_outbox')->insertOrIgnore([
                'business_id' => $businessId,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'event_type' => $eventType,
                'idempotency_key' => $key,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => $now,
                'processed_at' => null,
                'last_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $job = PdnewIntegrationOutbox::query()
                ->where('idempotency_key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $job->business_id !== $businessId
                || (string) $job->aggregate_type !== $aggregateType
                || (int) $job->aggregate_id !== $aggregateId
                || (string) $job->event_type !== $eventType
            ) {
                throw new RuntimeException('The Petro PD-New outbox idempotency record does not match the requested event.');
            }

            // A repeated queue request must never reset an event that is already
            // processing or processed. Before the first attempt, retaining the
            // latest payload is safe and keeps the event deterministic.
            if ((string) $job->status === 'pending' && (int) $job->attempts === 0) {
                $job->update([
                    'payload' => $payload,
                    'available_at' => $job->available_at ?: $now,
                ]);
            }

            return $job->fresh();
        }, 3);
    }
}
