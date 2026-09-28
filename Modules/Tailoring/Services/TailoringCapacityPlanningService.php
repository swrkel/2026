<?php

namespace Modules\Tailoring\Services;

class TailoringCapacityPlanningService
{
    public function calculateCapacity(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function departmentLoad(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function deliveryRisk(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
