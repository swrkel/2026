<?php

namespace Modules\DistributionNew\Services\Analytics;

use Modules\DistributionNew\Entities\DisnewReportDeliveryLog;
use Modules\DistributionNew\Entities\DisnewScheduledReport;

class DisnewScheduledReportService
{
    public function queueDelivery(DisnewScheduledReport $report): array
    {
        $recipients = [];
        if (in_array($report->delivery_channel, ['email','both'], true)) {
            foreach (array_filter(array_map('trim', explode(',', (string) $report->recipient_emails))) as $email) {
                $recipients[] = ['channel' => 'email', 'recipient' => $email];
            }
        }
        if (in_array($report->delivery_channel, ['sms','both'], true)) {
            foreach (array_filter(array_map('trim', explode(',', (string) $report->recipient_mobiles))) as $mobile) {
                $recipients[] = ['channel' => 'sms', 'recipient' => $mobile];
            }
        }
        foreach ($recipients as $item) {
            DisnewReportDeliveryLog::create([
                'business_id' => $report->business_id,
                'scheduled_report_id' => $report->id,
                'report_code' => $report->report_code,
                'delivery_channel' => $item['channel'],
                'recipient' => $item['recipient'],
                'status' => 'queued',
                'message' => 'Queued by Distribution New. SMS sending is delegated to the existing SMS module bridge.',
            ]);
        }
        return ['success' => true, 'queued' => count($recipients)];
    }
}
