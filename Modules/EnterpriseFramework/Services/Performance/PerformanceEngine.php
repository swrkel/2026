<?php

namespace Modules\EnterpriseFramework\Services\Performance;

class PerformanceEngine
{
    public function recommendations(): array
    {
        return [
            'Index transaction_date / created_at fields used by reports.',
            'Index business_id and location_id columns for multi-tenant branch filters.',
            'Use cached report snapshots for large date ranges.',
            'Use background jobs for large exports.',
        ];
    }
}
