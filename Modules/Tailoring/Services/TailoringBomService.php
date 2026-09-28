<?php

namespace Modules\Tailoring\Services;

class TailoringBomService
{
    public function estimateCost(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function materialRequirement(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function copyTemplateBom(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
