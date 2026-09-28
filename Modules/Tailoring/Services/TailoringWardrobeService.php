<?php

namespace Modules\Tailoring\Services;

class TailoringWardrobeService
{
    public function customerWardrobe(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function addGarmentRecord(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function reorderFromWardrobe(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
