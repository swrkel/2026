<?php
namespace Modules\AirlineTicketingNew\Services\Queue;

use Modules\AirlineTicketingNew\Entities\QueueExecution;

class QueueExecutionService
{
    public function start(int $businessId, string $jobCode, array $payload = []): QueueExecution
    {
        return QueueExecution::query()->create([
            'business_id' => $businessId,
            'job_code' => $jobCode,
            'status' => 'running',
            'payload_json' => $payload,
            'started_at' => now(),
        ]);
    }

    public function complete(QueueExecution $execution, ?string $message = null): void
    {
        $execution->update([
            'status' => 'completed',
            'message' => $message,
            'completed_at' => now(),
            'duration_ms' => $execution->started_at
                ? $execution->started_at->diffInMilliseconds(now())
                : null,
        ]);
    }

    public function fail(QueueExecution $execution, \Throwable $error): void
    {
        $execution->update([
            'status' => 'failed',
            'message' => $error->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
