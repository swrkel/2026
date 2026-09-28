<?php
namespace Modules\AirlineTicketingNew\Services\Gds;

use Modules\AirlineTicketingNew\Entities\GdsRequestLog;

class GdsRequestLogService
{
    public function start(int $businessId, string $provider, string $operation, array $request): GdsRequestLog
    {
        return GdsRequestLog::query()->create([
            'business_id' => $businessId,
            'provider_code' => $provider,
            'operation' => $operation,
            'request_payload' => $request,
            'status' => 'started',
            'started_at' => now(),
        ]);
    }

    public function complete(GdsRequestLog $log, array $response): void
    {
        $log->update([
            'response_payload' => $response,
            'status' => 'completed',
            'completed_at' => now(),
            'duration_ms' => $log->started_at ? $log->started_at->diffInMilliseconds(now()) : null,
        ]);
    }

    public function fail(GdsRequestLog $log, string $message): void
    {
        $log->update([
            'status' => 'failed',
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}
