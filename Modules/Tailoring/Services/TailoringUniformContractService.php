<?php

namespace Modules\Tailoring\Services;

class TailoringUniformContractService
{
    public function activeContracts(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function nextDeliveries(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function renewalAlerts(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
