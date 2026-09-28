<?php

namespace Modules\BeautySaloons\Reports;

use Modules\BeautySaloons\Entities\BeautyReceptionQueue;

class ReceptionQueueReport
{
    public function summary(array $filters = [])
    {
        return BeautyReceptionQueue::query()
            ->when($filters['business_id'] ?? null, fn ($q, $v) => $q->where('business_id', $v))
            ->when($filters['business_location_id'] ?? null, fn ($q, $v) => $q->where('business_location_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('arrival_at', '>=', $v))
            ->when($filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('arrival_at', '<=', $v))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
    }
}
