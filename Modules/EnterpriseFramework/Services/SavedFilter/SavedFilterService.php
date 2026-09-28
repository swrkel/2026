<?php

namespace Modules\EnterpriseFramework\Services\SavedFilter;

class SavedFilterService
{
    public function defaults(array $context = []): array
    {
        return [
            'location_id' => $context['location_id'] ?? null,
            'consolidated' => $context['consolidated'] ?? true,
            'date_range' => $context['date_range'] ?? null,
            'financial_year' => $context['financial_year'] ?? null,
        ];
    }
}
