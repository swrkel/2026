<?php

namespace Modules\AirlineTicketingNew\Services\Notifications;

use Modules\AirlineTicketingNew\Entities\NotificationLog;

class ModuleNotificationBridge
{
    public function queue(
        int $businessId,
        string $eventCode,
        string $channel,
        string $recipient,
        string $subject,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): NotificationLog {
        return NotificationLog::query()->create([
            'business_id' => $businessId,
            'event_code' => $eventCode,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'status' => 'queued',
        ]);
    }
}
