<?php

namespace Modules\DistributionNew\Reports;

use Modules\DistributionNew\Models\DisnewHealthCheck;

class DisnewStandaloneAuditReport
{
    public function latest(?int $businessId = null): array
    {
        $query = DisnewHealthCheck::query()->latest('id');
        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        return [
            'pass' => (clone $query)->where('status', 'pass')->count(),
            'warning' => (clone $query)->where('status', 'warning')->count(),
            'fail' => (clone $query)->where('status', 'fail')->count(),
            'rows' => $query->limit(200)->get(),
        ];
    }
}
