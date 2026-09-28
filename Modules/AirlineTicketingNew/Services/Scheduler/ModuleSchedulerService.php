<?php
namespace Modules\AirlineTicketingNew\Services\Scheduler;

use Modules\AirlineTicketingNew\Entities\ScheduledTaskLog;
use Modules\AirlineTicketingNew\Services\Analytics\AnalyticsSnapshotService;
use Modules\AirlineTicketingNew\Services\Operations\OperationalQueueService;

class ModuleSchedulerService
{
    public function runDaily(int $businessId): array
    {
        $log = ScheduledTaskLog::query()->create([
            'business_id' => $businessId,
            'task_code' => 'daily_module_tasks',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $queueCount = app(OperationalQueueService::class)->rebuild($businessId);
            $snapshot = app(AnalyticsSnapshotService::class)->create($businessId, now()->toDateString());

            $result = [
                'queue_tasks_refreshed' => $queueCount,
                'analytics_snapshot_id' => $snapshot->id,
            ];

            $log->update([
                'status' => 'completed',
                'completed_at' => now(),
                'context_json' => $result,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
