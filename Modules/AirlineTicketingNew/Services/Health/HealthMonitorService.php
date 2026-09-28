<?php
namespace Modules\AirlineTicketingNew\Services\Health;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\HealthIncident;

class HealthMonitorService
{
    public function scan(int $businessId): array
    {
        $checks = [
            'stuck_notifications' => DB::table('atn_notification_logs')
                ->where('business_id', $businessId)
                ->where('status', 'queued')
                ->where('created_at', '<', now()->subHour())
                ->count(),
            'stuck_workflows' => DB::table('atn_workflow_instances')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subDays(2))
                ->count(),
            'failed_queue_jobs' => DB::table('atn_queue_executions')
                ->where('business_id', $businessId)
                ->where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];

        foreach ($checks as $code => $count) {
            if ($count > 0) {
                HealthIncident::query()->firstOrCreate(
                    [
                        'business_id' => $businessId,
                        'incident_code' => $code,
                        'status' => 'open',
                    ],
                    [
                        'severity' => 'warning',
                        'description' => $count . ' issue(s) detected.',
                        'context_json' => ['count' => $count],
                        'detected_at' => now(),
                    ]
                );
            }
        }

        return $checks;
    }
}
