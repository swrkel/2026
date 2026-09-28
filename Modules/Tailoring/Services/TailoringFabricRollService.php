<?php

namespace Modules\Tailoring\Services;

class TailoringFabricRollService
{
    public function reserveFabric(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function issueFabric(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function returnUnusedFabric(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
