<?php

namespace Modules\Tailoring\Services;

class TailoringPatternService
{
    public function storePattern(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function latestPattern(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

    public function patternHistory(array $filters = []): array
    {
        return ['status' => 'ready', 'filters' => $filters];
    }

}
