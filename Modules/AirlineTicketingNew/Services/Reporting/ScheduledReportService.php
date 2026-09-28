<?php
namespace Modules\AirlineTicketingNew\Services\Reporting;

use Modules\AirlineTicketingNew\Entities\ScheduledReport;

class ScheduledReportService
{
    public function due(int $businessId)
    {
        return ScheduledReport::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->get();
    }

    public function markRun(ScheduledReport $report, \DateTimeInterface $nextRun): void
    {
        $report->update([
            'last_run_at' => now(),
            'next_run_at' => $nextRun,
        ]);
    }
}
