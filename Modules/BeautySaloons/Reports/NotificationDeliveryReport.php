<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyNotificationLog;

class NotificationDeliveryReport
{
    public function summary(array $filters = []): array
    {
        $query = BeautyNotificationLog::query();

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'retry' => (clone $query)->where('status', 'retry')->count(),
        ];
    }
}
