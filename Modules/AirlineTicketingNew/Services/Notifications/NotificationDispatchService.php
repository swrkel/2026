<?php

namespace Modules\AirlineTicketingNew\Services\Notifications;

use Modules\AirlineTicketingNew\Entities\NotificationLog;

class NotificationDispatchService
{
    public function dispatchQueued(int $businessId): int
    {
        $count = 0;

        NotificationLog::query()
            ->where('business_id', $businessId)
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (NotificationLog $log) use (&$count): void {
                // Bridge point for the application's standalone SMS/email modules.
                // No duplicate sender implementation is introduced here.
                $log->update([
                    'status' => 'ready_for_bridge',
                    'sent_at' => null,
                ]);
                $count++;
            });

        return $count;
    }
}
