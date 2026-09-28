<?php

namespace Modules\CommunicationHub\Services\Reports;

use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Entities\CommunicationHubProvider;
use Modules\CommunicationHub\Entities\CommunicationHubTemplate;

class CommunicationHubReportService
{
    public function summary(array $filters = []): array
    {
        $query = CommunicationHubMessage::query();
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'messages' => (clone $query)->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'cost' => (clone $query)->sum('cost'),
            'providers' => CommunicationHubProvider::count(),
            'templates' => CommunicationHubTemplate::count(),
        ];
    }
}
