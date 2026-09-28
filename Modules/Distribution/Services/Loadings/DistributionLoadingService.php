<?php

namespace Modules\Distribution\Services\Loadings;

use Modules\Distribution\Entities\DistributionLoading;
use Modules\Distribution\Entities\DistributionLoadingLine;

/**
 * Distribution-owned loading service seam.
 *
 * This class centralizes loading queries inside the Distribution module without
 * changing existing controller behavior. Controllers can be moved to this
 * service gradually in later cleanup stages.
 */
class DistributionLoadingService
{
    public function queryForBusiness(int $businessId)
    {
        return DistributionLoading::where('business_id', $businessId);
    }

    public function findForBusiness(int $businessId, int $loadingId): ?DistributionLoading
    {
        return $this->queryForBusiness($businessId)->find($loadingId);
    }

    public function findForBusinessOrFail(int $businessId, int $loadingId): DistributionLoading
    {
        return $this->queryForBusiness($businessId)->findOrFail($loadingId);
    }

    public function linesForLoading(int $loadingId)
    {
        return DistributionLoadingLine::where('distribution_loading_id', $loadingId)->get();
    }

    public function totalsForLoading(int $loadingId): array
    {
        $lines = $this->linesForLoading($loadingId);

        return [
            'quantity' => (float) $lines->sum('qty'),
            'amount' => (float) $lines->sum('final_total'),
            'line_count' => (int) $lines->count(),
        ];
    }
}
