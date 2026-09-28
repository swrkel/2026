<?php
namespace Modules\AirlineTicketingNew\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\PerformanceMetric;

class PerformanceMonitorService
{
    public function capture(int $businessId): array
    {
        $metrics = [
            'open_reservations' => DB::table('atn_reservations')
                ->where('business_id', $businessId)
                ->whereIn('status', ['reserved','confirmed','on_hold'])
                ->count(),
            'pending_workflows' => DB::table('atn_workflow_instances')
                ->where('business_id', $businessId)
                ->where('status', 'pending')
                ->count(),
            'queued_notifications' => DB::table('atn_notification_logs')
                ->where('business_id', $businessId)
                ->where('status', 'queued')
                ->count(),
            'open_operational_tasks' => DB::table('atn_operational_tasks')
                ->where('business_id', $businessId)
                ->where('status', 'open')
                ->count(),
        ];

        foreach ($metrics as $code => $value) {
            PerformanceMetric::query()->create([
                'business_id' => $businessId,
                'metric_code' => $code,
                'metric_value' => $value,
                'recorded_at' => now(),
            ]);
        }

        return $metrics;
    }
}
