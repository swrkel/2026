<?php

namespace Modules\CommunicationHub\Services;

use Modules\CommunicationHub\Entities\CommunicationHubMessage;

class CommunicationHubReportService
{
    public function deliveryReport(array $filters = [])
    {
        $query = CommunicationHubMessage::query()->latest();

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate(50);
    }
}
