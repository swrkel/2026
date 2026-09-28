<?php

namespace Modules\Tailoring\Services;

class TailoringWorkQueueService
{
    public function queueByDepartment(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function assignNext(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function markComplete(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
