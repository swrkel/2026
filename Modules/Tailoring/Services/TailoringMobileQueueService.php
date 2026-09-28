<?php

namespace Modules\Tailoring\Services;

use Illuminate\Support\Collection;

class TailoringMobileQueueService
{
    public function summary(array $filters = []): array
    {
        return [
            'description' => 'role focused mobile work queues',
            'filters' => $filters,
            'branch_id' => $filters['location_id'] ?? $filters['branch_id'] ?? null,
            'consolidated' => empty($filters['location_id']) && empty($filters['branch_id']),
            'total' => 0,
            'pending' => 0,
            'completed' => 0,
            'value' => 0,
        ];
    }

    public function list(array $filters = []): Collection
    {
        return collect();
    }
}
