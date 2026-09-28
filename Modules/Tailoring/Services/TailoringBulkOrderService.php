<?php

namespace Modules\Tailoring\Services;

class TailoringBulkOrderService
{
    public function createBulkOrder(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function generateJobCards(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function summarizeSizes(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
